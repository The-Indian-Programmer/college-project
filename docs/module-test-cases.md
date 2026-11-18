# Module-wise test catalogue

The following 41 scenarios preserve the report’s identifiers. This is a reusable acceptance catalogue, not an execution log. Actual automated results are recorded separately in `tests/artifacts/results.json`, `tests/artifacts/browser-results.json`, and `docs/verification.md`. Combined automated cases may cover parts of several catalogue scenarios; they do not imply exhaustive coverage of every possible input.

## Identity

| ID | Scenario and input | Expected outcome |
| --- | --- | --- |
| ID-01 | Valid registration: New student; valid unique details | Account saved with student role and a password hash. |
| ID-02 | Duplicate / missing fields: Existing username or blank required name | Request rejected; no duplicate or partial account. |
| ID-03 | Authentication: Valid active account; then wrong password / inactive account | Only valid active credentials establish access. |
| ID-04 | Role escalation: Student registration requests admin role | Privileged role not granted from public input. |
| ID-05 | Password change: Wrong current password; then valid change | Invalid change rejected; new password works after valid change. |
| ID-07 | Logout protection: Log out, then request protected page | Access requires authentication again. |

## Recovery

| ID | Scenario and input | Expected outcome |
| --- | --- | --- |
| ID-06 | Token lifecycle: Valid token; repeat token; expired token | Valid token changes password once; reused/expired token rejected. |
| SF-07 | Token update failure: Database exception after password update but before consuming token | Password and token state roll back together. |

## Admin

| ID | Scenario and input | Expected outcome |
| --- | --- | --- |
| AD-01 | Record maintenance: Valid new department / instructor / status change | Authorized changes saved and shown consistently. |
| AD-02 | Record constraints: Duplicate department; invalid department ID | Invalid update rejected without broken references. |
| AD-03 | History protection: Delete department/user referenced by an exam or registration | Deletion rejected; historical records retained. |
| AD-04 | Authorization: Student requests administrator operation | Access denied and database unchanged. |

## Exam setup

| ID | Scenario and input | Expected outcome |
| --- | --- | --- |
| EX-01 | Valid exam: Authorized creator; valid title, dates, duration | Exam saved under the correct department/creator. |
| EX-02 | Boundary validation: Zero/negative duration; closing time <= opening time | Invalid schedule rejected. |
| EX-03 | Ownership: Instructor edits another instructor's exam | Unauthorized update rejected. |
| EX-04 | Empty examination: Student opens exam with no valid questions | Attempt not opened; configuration error reported. |
| EX-05 | Started examination: Change schedule after an attempt exists | Change rejected; timing and historic assessment retained. |

## Question

| ID | Scenario and input | Expected outcome |
| --- | --- | --- |
| QU-01 | Valid MCQ: Two or more options; one correct; positive marks | Question and options saved together. |
| QU-02 | Invalid answer key: Zero or two correct options | Question cannot become available for an attempt. |
| QU-03 | True/false validation: True and False; then an extra third option | Valid pair accepted; invalid TF option set rejected. |
| QU-04 | Field boundaries: Blank question/option; zero or negative marks | Invalid data rejected with a field-specific error. |
| QU-05 | Answer-key protection: Fetch student question page / response data | Correct-option flag absent from student-facing data. |
| QU-06 | Started examination: Edit answer key or marks after first attempt | Update rejected; existing result basis preserved. |

## Exam taking

| ID | Scenario and input | Expected outcome |
| --- | --- | --- |
| ST-01 | Eligibility: Active registered student during open window | One attempt created with correct deadline. |
| ST-02 | Schedule boundaries: Before opening; at opening; at closing | Before/at closing rejected; opening accepted if otherwise eligible. |
| ST-03 | Duration boundary: Answer just before deadline; at/after deadline | Only pre-deadline answer update accepted. |
| ST-04 | Invalid answer reference: Option from another question or question from another exam | Response rejected; existing data unchanged. |
| ST-05 | Single-attempt rule: Repeat start after completed attempt | Second attempt refused; existing result retained. |
| ST-08 | Expiry and connection loss: Deadline passes; reconnect with late answer | Late changes rejected; server finalizes accepted answers when processed. |

## Evaluation

| ID | Scenario and input | Expected outcome |
| --- | --- | --- |
| ST-06 | Known scoring fixture: Correct 2-mark answer; wrong 3-mark; blank 5-mark | Stored and displayed score = 2 of 10. |
| ST-07 | Submission consistency: Double submit; database failure during finalization | At most one result; failed transaction leaves no partial completion. |

## Reports

| ID | Scenario and input | Expected outcome |
| --- | --- | --- |
| RP-01 | Counts: 3 registrations, 2 started attempts | Registered = 3; attended = 2. |

## Results

| ID | Scenario and input | Expected outcome |
| --- | --- | --- |
| RP-02 | Ownership / no result: Request another student's result; own unfinished attempt | Unauthorized result hidden; own unfinished result not shown as final. |

## Integration

| ID | Scenario and input | Expected outcome |
| --- | --- | --- |
| SC-01 | Unsafe input / forged request: SQL-like text, script markup, unauthorized state-changing request | Input remains data; no query/script execution or unauthorized write. |

## Concurrency

| ID | Scenario and input | Expected outcome |
| --- | --- | --- |
| SF-01 | Simultaneous starts: Two concurrent start requests for one registration | At most one attempt; consistent existing attempt returned. |
| SF-02 | Save/submission race: Response save concurrent with final submission | No post-finalization response changes; result reflects committed snapshot. |

## Transactions

| ID | Scenario and input | Expected outcome |
| --- | --- | --- |
| SF-03 | Finalization failure: Injected database exception before completion update | No result or completion is partially committed. |

## Authorization

| ID | Scenario and input | Expected outcome |
| --- | --- | --- |
| SF-04 | Result identity tampering: Change target student or attempt identifier | No other student result is disclosed. |

## Validation

| ID | Scenario and input | Expected outcome |
| --- | --- | --- |
| SF-05 | Field limits: Exact maximum and maximum plus one | Accept valid maximum and reject excess without truncation. |

## Scoring

| ID | Scenario and input | Expected outcome |
| --- | --- | --- |
| SF-06 | Aggregate overflow: Sum of question marks above DECIMAL(10,2) capacity | Reject configuration or finalization without truncation. |

## Connectivity

| ID | Scenario and input | Expected outcome |
| --- | --- | --- |
| SF-08 | Failed autosave: Abort save request then retry | No false saved confirmation; retry persists accepted answers. |

## Manual acceptance protocol

1. Record build, PHP/database/browser versions and time zone.
2. Use isolated seed data and identify the accounts and exam involved.
3. Execute each case independently and record actual output and persisted state.
4. Capture defects with reproduction steps; rerun failed and related cases after correction.
5. Validate external mail delivery, restoration and the institution’s selected concurrent-user workload before operational use.
6. Check keyboard access and layouts at 320, 390, 768, 1024 and 1440 pixels. Real devices and browsers remain a separate acceptance exercise.
