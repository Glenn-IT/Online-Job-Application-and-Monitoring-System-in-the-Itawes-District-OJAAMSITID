http://localhost/OJAMS/pages/user/saved-jobs.php
    - [COMPLETED] Saved Jobs & Draft Applications feature fully operational:
      * Fixed SQLSTATE[HY093] parameter binding crash when querying saved_jobs & applications.
      * Restored end-to-end Draft loading and population in the application modal.
      * Integrated standard OJAMS confirmation modal (`showConfirmModal`) for removing saved jobs.
      * Included applicant session email, mandatory diploma document, and loading modal feedback (`showLoadingModal` / `hideLoadingModal`) on submission.
      * Verified seamless synchronization with `handlers/saved-jobs.php` and `handlers/applications.php`.