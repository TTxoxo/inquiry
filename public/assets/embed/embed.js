(function () {
  'use strict';

  var SCRIPT_ATTR = 'data-inquiry-embed-loaded';
  var CSS_ID = 'inquiry-embed-styles';
  var INLINE_SELECTOR = '.inquiry-embed';
  var DEFAULT_MESSAGES = {
    loadError: 'Unable to load the form right now.',
    submitError: 'Unable to submit the form right now.',
    networkError: 'Network error, please try again later.',
    required: 'This field is required.'
  };

  if (window.InquiryEmbed && window.InquiryEmbed.__initialized) {
    return;
  }

  function currentScript() {
    return document.currentScript || document.querySelector('script[' + SCRIPT_ATTR + '="1"]') || null;
  }

  function markScript(script) {
    if (script) {
      script.setAttribute(SCRIPT_ATTR, '1');
    }
  }

  function parseQuery(input) {
    var query = input || '';
    if (query.indexOf('?') >= 0) {
      query = query.split('?')[1] || '';
    }
    if (query.charAt(0) === '#') {
      query = '';
    }
    return new URLSearchParams(query);
  }

  function resolveApiBase(script) {
    if (!script || !script.src) {
      return '';
    }
    var url = new URL(script.src, window.location.href);
    return url.origin;
  }

  function ensureStyles(script) {
    if (document.getElementById(CSS_ID)) {
      return;
    }
    var href = '';
    if (script && script.src) {
      href = script.src.replace(/embed\.js(?:\?.*)?$/, 'embed.css');
    }
    if (!href) {
      return;
    }
    var link = document.createElement('link');
    link.id = CSS_ID;
    link.rel = 'stylesheet';
    link.href = href;
    document.head.appendChild(link);
  }

  function text(value) {
    return value == null ? '' : String(value);
  }

  function firstNonEmpty() {
    for (var i = 0; i < arguments.length; i += 1) {
      var value = text(arguments[i]).trim();
      if (value !== '') {
        return value;
      }
    }
    return '';
  }

  function normalizeMode(mode) {
    return mode === 'floating' ? 'floating' : 'inline';
  }

  function createElement(tag, className, content) {
    var element = document.createElement(tag);
    if (className) {
      element.className = className;
    }
    if (content != null) {
      element.textContent = content;
    }
    return element;
  }

  function extractTracking() {
    var url = new URL(window.location.href);
    return {
      page_meta: {
        page_url: window.location.href,
        page_title: document.title || '',
        referrer_url: document.referrer || ''
      },
      tracking_meta: {
        utm_source: url.searchParams.get('utm_source') || '',
        utm_medium: url.searchParams.get('utm_medium') || '',
        utm_campaign: url.searchParams.get('utm_campaign') || '',
        utm_term: url.searchParams.get('utm_term') || '',
        utm_content: url.searchParams.get('utm_content') || '',
        gclid: url.searchParams.get('gclid') || '',
        gbraid: url.searchParams.get('gbraid') || '',
        wbraid: url.searchParams.get('wbraid') || ''
      }
    };
  }

  function request(url, options) {
    return fetch(url, options).then(function (response) {
      return response.text().then(function (body) {
        var payload;
        try {
          payload = body ? JSON.parse(body) : {};
        } catch (error) {
          throw new Error(DEFAULT_MESSAGES.networkError);
        }
        if (!response.ok) {
          var message = payload && payload.message ? payload.message : DEFAULT_MESSAGES.networkError;
          var requestError = new Error(message);
          requestError.payload = payload;
          throw requestError;
        }
        return payload;
      });
    });
  }

  function safePushDataLayer(payload) {
    if (!payload || typeof payload !== 'object') {
      return false;
    }
    if (!Array.isArray(window.dataLayer)) {
      return false;
    }
    window.dataLayer.push(payload);
    return true;
  }

  function buildField(field, state) {
    var wrapper = createElement('div', 'inquiry-embed__field');
    var label = createElement('label', 'inquiry-embed__label', text(field.label));
    var isRequired = Number(field.is_required) === 1;
    var fieldId = 'inquiry-embed-field-' + state.instanceId + '-' + text(field.name);
    var settings = field.settings && typeof field.settings === 'object' ? field.settings : {};
    label.setAttribute('for', fieldId);
    if (isRequired) {
      var requiredMark = createElement('span', 'inquiry-embed__required', ' *');
      label.appendChild(requiredMark);
    }
    wrapper.appendChild(label);

    var input;
    var type = text(field.type).toLowerCase();
    if (type === 'textarea') {
      input = document.createElement('textarea');
      input.rows = 4;
    } else if (type === 'select') {
      input = document.createElement('select');
      var emptyOption = document.createElement('option');
      emptyOption.value = '';
      emptyOption.textContent = settings.placeholder || 'Please select';
      input.appendChild(emptyOption);
      var options = Array.isArray(settings.options) ? settings.options : [];
      options.forEach(function (option) {
        var normalized = typeof option === 'object' && option !== null
          ? { label: text(option.label || option.value), value: text(option.value || option.label) }
          : { label: text(option), value: text(option) };
        var optionElement = document.createElement('option');
        optionElement.value = normalized.value;
        optionElement.textContent = normalized.label;
        input.appendChild(optionElement);
      });
    } else {
      input = document.createElement('input');
      input.type = type === 'email' || type === 'tel' || type === 'number' ? type : 'text';
    }

    input.className = 'inquiry-embed__input';
    input.id = fieldId;
    input.name = text(field.name);
    input.placeholder = text(settings.placeholder || '');
    input.setAttribute('autocomplete', 'off');
    if (isRequired) {
      input.required = true;
      input.setAttribute('aria-required', 'true');
    }
    wrapper.appendChild(input);

    var error = createElement('div', 'inquiry-embed__field-error');
    error.setAttribute('data-error-for', text(field.name));
    wrapper.appendChild(error);

    return wrapper;
  }

  function setStatus(state, kind, message) {
    state.statusNode.className = 'inquiry-embed__status inquiry-embed__status--' + kind;
    state.statusNode.textContent = text(message);
  }

  function clearFieldErrors(state) {
    Array.prototype.forEach.call(state.formNode.querySelectorAll('.inquiry-embed__field-error'), function (node) {
      node.textContent = '';
    });
    Array.prototype.forEach.call(state.formNode.querySelectorAll('.inquiry-embed__input'), function (node) {
      node.classList.remove('is-invalid');
    });
  }

  function setFieldError(state, fieldName, message) {
    var input = state.formNode.querySelector('[name="' + fieldName + '"]');
    var error = state.formNode.querySelector('[data-error-for="' + fieldName + '"]');
    if (input) {
      input.classList.add('is-invalid');
    }
    if (error) {
      error.textContent = text(message);
    }
  }

  function collectFields(state) {
    var payload = {};
    Array.prototype.forEach.call(state.formNode.querySelectorAll('.inquiry-embed__input'), function (node) {
      payload[node.name] = text(node.value).trim();
    });
    return payload;
  }

  function validateRequired(state) {
    clearFieldErrors(state);
    var values = collectFields(state);
    var isValid = true;
    state.config.fields.forEach(function (field) {
      if (Number(field.is_required) === 1 && text(values[field.name]).trim() === '') {
        setFieldError(state, field.name, DEFAULT_MESSAGES.required);
        isValid = false;
      }
    });
    return { valid: isValid, values: values };
  }

  function setSubmitting(state, submitting) {
    state.submitNode.disabled = submitting;
    state.submitNode.setAttribute('aria-busy', submitting ? 'true' : 'false');
    state.submitNode.textContent = submitting ? 'Submitting...' : state.submitText;
  }

  function buildSubmitPayload(state, fields) {
    var meta = extractTracking();
    return {
      site_key: state.siteKey,
      form_key: state.formKey,
      fields: fields,
      page_meta: meta.page_meta,
      tracking_meta: meta.tracking_meta,
      honeypot: ''
    };
  }

  function flattenErrors(errors) {
    if (!errors || typeof errors !== 'object') {
      return '';
    }
    var keys = Object.keys(errors);
    return keys.length > 0 ? text(errors[keys[0]]) : '';
  }

  function handleSubmit(state, event) {
    event.preventDefault();
    var validation = validateRequired(state);
    if (!validation.valid) {
      setStatus(state, 'error', 'Please complete the required fields.');
      return;
    }

    setSubmitting(state, true);
    setStatus(state, 'info', 'Submitting...');

    request(state.apiBase + '/api/form/submit', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(buildSubmitPayload(state, validation.values))
    }).then(function (response) {
      var payload = response && response.data ? response.data : {};
      clearFieldErrors(state);
      state.formNode.reset();
      setStatus(state, 'success', payload.success_message || state.config.success_message || 'Submitted successfully.');
      safePushDataLayer(payload.data_layer_payload || null);
      if (state.mode === 'floating') {
        state.root.classList.add('is-expanded');
      }
    }).catch(function (error) {
      var payload = error && error.payload ? error.payload : {};
      var errors = payload && payload.errors && typeof payload.errors === 'object' ? payload.errors : {};
      clearFieldErrors(state);
      Object.keys(errors).forEach(function (fieldName) {
        setFieldError(state, fieldName, errors[fieldName]);
      });
      var message = flattenErrors(errors) || (payload && payload.message) || error.message || DEFAULT_MESSAGES.submitError;
      if (message === 'Failed to fetch') {
        message = DEFAULT_MESSAGES.networkError;
      }
      setStatus(state, 'error', message);
    }).finally(function () {
      setSubmitting(state, false);
    });
  }

  function renderForm(state) {
    var card = createElement('section', 'inquiry-embed__card');
    var header = createElement('div', 'inquiry-embed__header');
    header.appendChild(createElement('h3', 'inquiry-embed__title', text(state.config.form.name || 'Inquiry Form')));
    if (text(state.config.form.description)) {
      header.appendChild(createElement('p', 'inquiry-embed__description', text(state.config.form.description)));
    }
    card.appendChild(header);

    if (state.config.pre_notice && state.config.pre_notice.enabled && text(state.config.pre_notice.text)) {
      card.appendChild(createElement('div', 'inquiry-embed__notice', text(state.config.pre_notice.text)));
    }

    var form = createElement('form', 'inquiry-embed__form');
    form.noValidate = true;
    state.config.fields.forEach(function (field) {
      form.appendChild(buildField(field, state));
    });

    var honeypotWrap = createElement('div', 'inquiry-embed__honeypot');
    var honeypotInput = document.createElement('input');
    honeypotInput.type = 'text';
    honeypotInput.name = 'website';
    honeypotInput.tabIndex = -1;
    honeypotInput.autocomplete = 'off';
    honeypotWrap.appendChild(honeypotInput);
    form.appendChild(honeypotWrap);

    var actions = createElement('div', 'inquiry-embed__actions');
    var submit = createElement('button', 'inquiry-embed__submit', text(state.submitText));
    submit.type = 'submit';
    actions.appendChild(submit);
    form.appendChild(actions);

    var status = createElement('div', 'inquiry-embed__status');
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    form.appendChild(status);

    form.addEventListener('submit', handleSubmit.bind(null, state));
    state.formNode = form;
    state.submitNode = submit;
    state.statusNode = status;
    card.appendChild(form);

    if (state.mode === 'floating') {
      var panel = createElement('div', 'inquiry-embed__floating-panel');
      var panelHeader = createElement('div', 'inquiry-embed__floating-header');
      panelHeader.appendChild(createElement('div', 'inquiry-embed__floating-heading', text(state.config.form.name || 'Contact us')));
      var closeButton = createElement('button', 'inquiry-embed__close', '×');
      closeButton.type = 'button';
      closeButton.setAttribute('aria-label', 'Close form');
      closeButton.addEventListener('click', function () {
        state.root.classList.remove('is-expanded');
      });
      panelHeader.appendChild(closeButton);
      panel.appendChild(panelHeader);
      panel.appendChild(card);

      var toggle = createElement('button', 'inquiry-embed__toggle', 'Contact us');
      toggle.type = 'button';
      toggle.addEventListener('click', function () {
        state.root.classList.toggle('is-expanded');
      });

      state.root.innerHTML = '';
      state.root.appendChild(toggle);
      state.root.appendChild(panel);
      return;
    }

    state.root.innerHTML = '';
    state.root.appendChild(card);
  }

  function renderLoadError(state, error) {
    state.root.innerHTML = '';
    state.root.appendChild(createElement('div', 'inquiry-embed__fallback inquiry-embed__fallback--error', error));
  }

  function mountInstance(root, options) {
    var state = {
      instanceId: options.instanceId,
      root: root,
      mode: normalizeMode(options.mode),
      siteKey: options.siteKey,
      formKey: options.formKey,
      apiBase: options.apiBase,
      submitText: 'Submit',
      config: null,
      formNode: null,
      submitNode: null,
      statusNode: null
    };

    root.classList.add('inquiry-embed-host', 'inquiry-embed-host--' + state.mode);
    if (state.mode === 'floating') {
      root.classList.add('inquiry-embed-floating');
    }
    root.innerHTML = '';
    root.appendChild(createElement('div', 'inquiry-embed__fallback', 'Loading form...'));

    var configUrl = state.apiBase + '/api/form/config?site_key=' + encodeURIComponent(state.siteKey) + '&form_key=' + encodeURIComponent(state.formKey) + '&mode=' + encodeURIComponent(state.mode);

    request(configUrl, { method: 'GET', credentials: 'same-origin' }).then(function (response) {
      var payload = response && response.data ? response.data : null;
      if (!payload || !Array.isArray(payload.fields)) {
        throw new Error(DEFAULT_MESSAGES.loadError);
      }
      state.config = payload;
      state.submitText = firstNonEmpty(payload.style_config && payload.style_config.submit_button_text, 'Submit');
      renderForm(state);
    }).catch(function (error) {
      var payload = error && error.payload ? error.payload : {};
      renderLoadError(state, (payload && payload.message) || error.message || DEFAULT_MESSAGES.loadError);
    });
  }

  function collectInlineTargets(defaults) {
    var selector = defaults.containerSelector;
    if (selector) {
      return Array.prototype.slice.call(document.querySelectorAll(selector));
    }
    return Array.prototype.slice.call(document.querySelectorAll(INLINE_SELECTOR));
  }

  function initInline(defaults) {
    collectInlineTargets(defaults).forEach(function (node, index) {
      var siteKey = firstNonEmpty(node.getAttribute('data-site-key'), defaults.siteKey);
      var formKey = firstNonEmpty(node.getAttribute('data-form-key'), node.getAttribute('data-form-code'), defaults.formKey);
      var mode = normalizeMode(firstNonEmpty(node.getAttribute('data-mode'), defaults.mode, 'inline'));
      if (!siteKey || !formKey || node.getAttribute('data-embed-mounted') === '1') {
        return;
      }
      node.setAttribute('data-embed-mounted', '1');
      mountInstance(node, {
        instanceId: 'inline-' + index,
        siteKey: siteKey,
        formKey: formKey,
        mode: mode,
        apiBase: defaults.apiBase
      });
    });
  }

  function initFloating(defaults) {
    if (normalizeMode(defaults.mode) !== 'floating') {
      return;
    }
    var container = document.createElement('div');
    container.setAttribute('data-embed-mounted', '1');
    document.body.appendChild(container);
    mountInstance(container, {
      instanceId: 'floating-0',
      siteKey: defaults.siteKey,
      formKey: defaults.formKey,
      mode: 'floating',
      apiBase: defaults.apiBase
    });
  }

  function init(customOptions) {
    var script = currentScript();
    markScript(script);
    ensureStyles(script);
    var params = parseQuery(script && script.src ? script.src : '');
    var defaults = {
      siteKey: firstNonEmpty(customOptions && customOptions.siteKey, params.get('site_key')),
      formKey: firstNonEmpty(customOptions && customOptions.formKey, params.get('form_key'), params.get('form_code')),
      mode: normalizeMode(firstNonEmpty(customOptions && customOptions.mode, params.get('mode'), 'inline')),
      containerSelector: firstNonEmpty(customOptions && customOptions.container),
      apiBase: firstNonEmpty(customOptions && customOptions.apiBase, resolveApiBase(script))
    };

    if (!defaults.siteKey || !defaults.formKey || !defaults.apiBase) {
      return;
    }

    if (defaults.mode === 'floating') {
      initFloating(defaults);
      return;
    }

    initInline(defaults);
  }

  window.InquiryEmbed = {
    __initialized: true,
    init: init,
    pushDataLayer: safePushDataLayer
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      init();
    });
  } else {
    init();
  }
}());
