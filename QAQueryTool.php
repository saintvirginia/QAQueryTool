<?php

class QAQueryTool{
    public static function run() {
        $state = array();
        $repField = $_REQUEST['repField'];
        $repValue = $_REQUEST['repValue'];

        // Start of getRep
        $rep = null;

        // Search Rep by Phone
        if($repField === 'MobilePhone') {
            $phone = preg_replace("/[^0-9]/", "", addslashes($repValue));

            if (!is_numeric($phone)) {
                return false; //this is all we need to prevent mysql injections
            }

            if (($reps = DataObject::get(MVRep::NAME, "(MobilePhone = '$phone')", null, 'LEFT JOIN MVRep_Employers ON MVRep.ID = MVRep_Employers.MVRepID', '1'))
                && $reps->Count()) {
                $rep = $reps->First();
            }
        }

        // Search Rep by ID
        else if($repField === 'ID' && is_numeric($repValue)){
            $rep = DataObject::get_by_id(MVRep::NAME, $repValue);
        }

        // Search Rep by MVID
        else if($repField === 'MVID'){
            $MVID = $repValue;
            ($reps = DataObject::get(MVRep::NAME, "(MVID = '$MVID')", null, 'LEFT JOIN MVRep_Employers ON MVRep.ID = MVRep_Employers.MVRepID', '1'))
                && $reps->Count();
                $rep = $reps->First();
        }

        // Search Rep by Firstname
//        else if($repField === 'Firstname'){
//            $FirstName = $repValue;
//            ($reps = DataObject::get(MVRep::NAME, "(Firstname = '$FirstName')", null, 'LEFT JOIN MVRep_Employers ON MVRep.ID = MVRep_Employers.MVRepID'))
//            && $reps->Count();
//            $rep = $reps->First();
//        }


        // Return of getRep
        $state['rep'] = $rep;



        // Start of buildView
        $output = '<div style="display: flex;flex-flow: column nowrap;height: 95vh;border-bottom-style: solid;">';

        // Start of buildHeaderView
        $url =  "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
        $html = '<div style="flex-basis: 108px;flex-shrink: 1;border-bottom-style: solid;align-self: stretch;display: flex;flex-flow: row wrap;justify-content: space-between;align-items: flex-end;">';
        $html .= '<div style="order:1;flex-grow:2;margin-block-end: 1em">
                    <h1 style="margin:20px"><a href="'.$url.'">QA Query Tool</a></h1>
                    
                </div>';

        // Start of buildRepInputView
        $input = '<h2 >Enter Rep identifier below to find a Rep</h2>';
        $input .= '<form action="" method="post"><input type="hidden" name="repmobilephonelookup" value="true" />';
        $input .= '<div style="margin-bottom: 5px;">Rep
                    <select name="repField">
                        <option value="MobilePhone">MobilePhone</option>
                        <option value="ID">ID</option>
                        <option value="MVID">MVID</option>
                        <option value="Firstname">Firstname</option>
                    </select>
                    <input type="text" name="repValue" size="11" />
                    <input type="submit" value="Look Up Rep"/>
                    </div>';


        // Return of buildInputView
        $html .= '<div style="order:2;flex-basis:33%;flex-grow:1">'.$input . '</form>'. '</div>';

        // Return of buildHeaderView
        $output .= $html.'</div>';

        // buildView cont.
        if($state['rep']){  // ??
            $output .= '<div style="flex-grow: 1;display: flex;flex-flow: row wrap;justify-content: center;align-content: flex-start;align-items: flex-start;overflow-y: auto;">';

            $records = DataObject::get(
                MVRep::NAME,
                "MVRep.ID=" . $state['rep']->ID,null, 'LEFT JOIN MVRep_Employers ON MVRep.ID = MVRep_Employers.MVRepID', '1');

            $output .= '<div style="border-style: solid;border-radius: 5px;border-color: #2f4f4f;margin: 5px;padding: 5px;">';
            $output .= "<h2 style='padding: 10px'>Rep Details:</h2>";
            $output .= self::buildOutputTable("Rep Details", $records, array('ID'=>'ID', 'Created'=>'Created', 'FirstName'=>'Firstname', 'LastName'=>'Lastname', 'EmployeeID'=>'Employee ID', 'MVID'=>'MVID'));

            $output .= "</div>";

            $records = DataObject::get(
                "MVWorkLogEntry",
                "MVWorkLogEntry.RepID=" . $state['rep']->ID);
            foreach($records as $record) {
                switch ($record->ClassName) {
                    case "MVWorkLogEntry":
                        $mPlan = DataObject::get_by_id(MVmPlan::NAME, $record->mPlanID);
                        $record->mPlanValue = $record->mPlanID . " - " . $mPlan->Name;
                        $Store = DataObject::get_by_id(MVStore::NAME, $record->StoreID);
                        $record->StoreValue = $record->StoreID . " - " . $Store->Name;
                        break;
                    case "MVWorkLogCall":
                        $Store = DataObject::get_by_id(MVStore::NAME, $record->StoreID);
                        $record->StoreValue = $record->StoreID . " - " . $Store->Name;
                        break;

                    }
                }


            $output .= '<div style="border-style: solid;border-radius: 5px;border-color: darkslategrey;margin: 5px;padding: 5px;">';
            $output .= "<h2 style='padding: 10px'>Rep WorkLogEntry:</h2>";
            $output .= self::buildOutputTable("Work Log Entry", $records, array('ID'=>'ID', 'ClassName'=>'WLE Class Name', 'Created'=>'Created', 'StartTime'=>'Start Time', 'EndTime'=>'End Time','mPlanValue'=>'mPlan ID - Name', 'StoreValue'=>'Location ID - Name'));
            $output .= "</div>";

            $records = DataObject::get(
                "MVAssignment",
                "MVAssignment.AssociatedObjectID=" . $state['rep']->ID." and MVAssignment.IsDeleted = 0");
            foreach($records as $record) {

                switch($record->AssignmentSubType) {
                    case "RetailerID":
                        $Retailer = DataObject::get_by_id(MVRetailer::NAME, $record->AssignmentValue);
                        $record->AssignmentValueOutput = $record->AssignmentValue." - ".$Retailer->Name;
                        break;
                    case "BrandID":
                        $Brand = DataObject::get_by_id(MVBrand::NAME, $record->AssignmentValue);
                        $record->AssignmentValueOutput = $record->AssignmentValue." - ".$Brand->Name;
                        break;
                    case "StoreID":
                        $Store = DataObject::get_by_id(MVStore::NAME, $record->AssignmentValue);
                        $record->AssignmentValueOutput = $record->AssignmentValue." - ".$Store->Name;
                        break;
                    default:
                        $record->AssignmentValueOutput = $record->AssignmentValue;
                        break;
                }
            }
            $output .= '<div style="border-style: solid;border-radius: 5px;border-color: darkslategrey;margin: 5px;padding: 5px;">';
            $output .= "<h2 style='padding: 10px'>Rep Assignments:</h2>";
            $output .= self::buildOutputTable("Assignments", $records, array('ID'=>'ID', 'Created'=>'Created', 'LastEdited'=>'Last Edited', 'AssignmentType'=>'Assignment Type','AssignmentValueOutput'=>'Assignment ID - Value','MVID'=>'MVID'));
            $output .= "</div>";


            $records = DataObject::get(
                "MVScheduledEvent",
               "MVScheduledmPlan.RepID=" . $state['rep']->ID." or MVScheduledLocation.RepID=" . $state['rep']->ID." or MVScheduledAvailability.RepID=" . $state['rep']->ID, "Start desc");

            foreach($records as $record) {
                switch ($record->ClassName) {
                    case "MVScheduledmPlan":
                        $mPlan = DataObject::get_by_id(MVmPlan::NAME, $record->mPlanID);
                        $record->mPlanValue = $record->mPlanID . " - " . $mPlan->Name;
                        $Location = DataObject::get_by_id(MVStore::NAME, $record->LocationID);
                        $record->LocationValue = $record->LocationID . " - " . $Location->Name;
                        break;
                    case "MVScheduledLocation":
                        $Location = DataObject::get_by_id(MVStore::NAME, $record->LocationID);
                        $record->LocationValue = $record->LocationID . " - " . $Location->Name;
                        break;

                }
            }

            $output .= '<div style="border-style: solid;border-radius: 5px;border-color: darkslategrey;margin: 5px;padding: 5px;">';
            $output .= "<h2 style='padding: 10px'>Rep Schedule:</h2>";
            $output .= self::buildOutputTable("Schedule", $records, array('ID'=>'ID', 'ClassName'=> 'Classname', 'Start'=>'Start','End'=>'End','mPlanValue'=>'mPlan ID - Name', 'LocationValue'=>'Location ID - Name', 'MVID'=>'MVID'));
            $output = $output."</div>";//

        } else if(!empty($state['repField']) && !empty($state['repValue'])) {
            $output .= "<div>No Rep found with " . $state['repField'] . ": " . $state['repValue'] . "</div><br/>";
        }

        return $output . "</div>";

    }

    /**
     * buildOutputTable("Work Log Entry For Rep", $records, array('ID'=>'ID', 'ClassName'=>'WLE Class Name'));
     *
     * @param $ormType String for title of the table
     * @param $records result from DataObject::get(...)
     * @param $tableConfig array('ID'=>'ID', 'ClassName'=>'WLE Class Name')
     */
    private static function buildOutputTable($ormType, $records, $tableConfig) {
        $output = '';
        $c = count($records);
        if($c > 0) {
            $output .= "<table>";
            // Build table header
            $output .= "<tr>";
            foreach($tableConfig as $key => $value) {
                $output .= "<th style='padding:10px 20px;'>{$value}</th>";
            }
            $output .= "</tr>";
            foreach($records as $record) {
                $output .= "<tr>";
                // output each row
                foreach($tableConfig as $key => $value) {
                    $outputValue = $record->{$key};
                    $output .= "<td style='padding:10px 20px;'>{$outputValue}</td>";
                }
                $output .= "</tr>";
            }
            $output .= "</table>";
        } else {
            $output .= "There are no {$ormType} records to show";
        }
        return $output;
    }
}
