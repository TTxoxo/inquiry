# AGENTS.md

## Project Positioning
- This repository is a formal ThinkPHP 8 project, not a demo, sample, or temporary scaffold.
- The project is built in stages. Only the current stage requested by the user may be implemented.
- Do not implement future-stage functionality in advance.
- Current workflow: one stage, one task, one PR.

## Architecture Constraints
- Only one ThinkPHP 8 architecture is allowed.
- Only one database schema/system is allowed.
- Only one routing system is allowed.
- Only one controller set is allowed.
- Only one service layer is allowed.
- Do not generate any second legacy MVC structure, alternate route set, alternate controller set, or alternate service set.

## Naming and Directory Rules
- All directories must be lowercase.
- Namespaces must uniformly use `app\\...`.
- View file suffixes must uniformly use `.html`.

## Response Format Rules
All admin AJAX endpoints, install interfaces, and public/open APIs must uniformly return JSON.

Success response:
```json
{"code":0,"message":"ok","data":{}}
```

Failure response:
```json
{"code":非0,"message":"错误说明","data":{},"errors":{}}
```

## Implementation Prohibitions
- Do not write TODOs.
- Do not leave placeholders.
- Do not write pseudocode.
- Do not defer required work with notes such as “complete later”.
- Do not modify stabilized field names, class names, route names, or response formats established by earlier approved stages.

## Release and Delivery Rules
- Official release packages must include `vendor`.
- At the end of each stage, try to run the following commands before stopping:
  - `composer install --no-dev --optimize-autoloader`
  - `php think`
- At the end of each stage, the response must include:
  - completed file list
  - executed commands
  - verification results
  - next-stage boundary

## Current Stage Scope Discipline
- Only perform the exact work requested in the current stage.
- If the current stage is guidance/documentation only, do not create business code, installer code, SQL, routes, controllers, services, models, or runtime logic.
- Stop after completing the current stage and wait for the next prompt.
