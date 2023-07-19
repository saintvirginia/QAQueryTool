# QAQueryTool

Internal admin page for Movista QA engineers to use to query the database for relevant data instead of needing db access to every domain.

runs off the function:

 public function qaQueryTool() {
        return QAQueryTool::run();
    }

in AdminTasks.php
