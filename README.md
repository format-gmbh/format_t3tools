# format_t3tools
This TYPO3 extension checks the size of all database tables and/or the size of all log files at regular intervals. If a certain size is exceeded, a mail can be sent. 
There is a separate scheduler task for each check.

<h2>Note for updating from 1.x to 2.x</h2>
After updating the extension from version 1.x to 2.x please delete the old Scheduler Task and create a new one.
