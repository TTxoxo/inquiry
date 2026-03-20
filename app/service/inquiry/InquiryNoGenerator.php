<?php

declare(strict_types=1);

namespace app\service\inquiry;

final class InquiryNoGenerator
{
    public function generate(int $siteId, int $formId, int $inquiryId = 0): string
    {
        return sprintf('IQ-%d-%d-%s%s', $siteId, $formId, date('YmdHis'), $inquiryId > 0 ? '-' . $inquiryId : '');
    }
}
