<?php

require_once 'settings.php';
set_include_path( get_include_path() . ':' . ROOT . "/lib/ZendGdata-1.7.6/library" );
require_once 'Zend/Loader.php';

Zend_Loader::loadClass('Zend_Gdata');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
Zend_Loader::loadClass('Zend_Gdata_Spreadsheets');
Zend_Loader::loadClass('Zend_Gdata_App_AuthException');
Zend_Loader::loadClass('Zend_Http_Client');


class DB
{
    public function __construct()
    {
        try {
          $client = Zend_Gdata_ClientLogin::getHttpClient(EMAIL, PASS,
                    Zend_Gdata_Spreadsheets::AUTH_SERVICE_NAME);
        } catch (Zend_Gdata_App_AuthException $ae) {
          exit("Error: ". $ae->getMessage() ."\nCredentials provided were email: [xxx] and password [xxx].\n");
        }
        $this->gdClient = new Zend_Gdata_Spreadsheets($client);
        $this->currKey = '';
        $this->currWkshtId = '';
        $this->listFeed = '';
        $this->rowCount = 0;
        $this->columnCount = 0;

        $this->listFeedData = null;
        $this->rowIndex = 0;

        $this->changeSpreadsheet(SHEET);
        $this->changeWorksheet(WKSHT);
    }

    public function changeSpreadsheet($name)
    {
        $feed = $this->gdClient->getSpreadsheetFeed();
        $index = $this->getIndexOf($name, $feed);
        $currKey = split('/', $feed->entries[$index]->id->text);
        $this->currKey = $currKey[5];
    }

    public function changeWorksheet($name)
    {
        $query = new Zend_Gdata_Spreadsheets_DocumentQuery();
        $query->setSpreadsheetKey($this->currKey);
        $feed = $this->gdClient->getWorksheetFeed($query);

        $index = $this->getIndexOf($name, $feed);
        $currWkshtId = split('/', $feed->entries[$index]->id->text);
        $this->currWkshtId = $currWkshtId[8];
    }

    private function getData()
    {
        $query = $this->createListQuery();
        $query->setReverse('true');
        $listFeed = $this->gdClient->getListFeed($query);
        $this->listFeedData = $listFeed;
        return;

        /*
        $rowData = $listFeed->entries[0]->getCustom();
        foreach($rowData as $customEntry) {
            $data[$customEntry->getColumnName()] = "a";
        }
        print_r( $data );
        */

        foreach($listFeed->entries as $entries) {
            $rowData = $entries->getCustom();
            foreach($rowData as $customEntry) {
                echo $customEntry->getColumnName() . " = " . $customEntry->getText() . "<br/>";
            }
        }
    }

    public function getNextRow()
    {
        if( $this->listFeedData == null ) {
            $this->getData();
            $this->rowIndex = 0;
        }

        if(!isset($this->listFeedData->entries[$this->rowIndex]))
            return null;

        $rowData = $this->listFeedData->entries[$this->rowIndex]->getCustom();
        $data = array();
        foreach($rowData as $customEntry) {
            $data = array_merge( $data,
                                 array($customEntry->getColumnName() => $customEntry->getText()) );
        }

        $this->rowIndex++;
        return $data;
    }

    public function insertRow($data)
    {
        $this->gdClient->insertRow($data, $this->currKey, $this->currWkshtId);
    }

    public function updateRows($field, $value, $newdata)
    {
        $query = $this->createListQuery();
        $query->spreadsheetQuery = "$field=$value";
        try{ $listFeed = $this->gdClient->getListFeed($query); }
        catch (Exception $e) { die( $e->getMessage() ); return null; }
        if( $listFeed == null || !isset($listFeed->entries[0]) ) {
            return null;
        }

        foreach( $listFeed->entries as $entry ) {
            try{ $new_entry = $this->gdClient->updateRow( $entry, $newdata ); }
            catch (Exception $e){ die("got exception!!:" . $e->getMessage()); return -2; }
            //print_r( $new_entry );
            if( $new_entry instanceof Zend_Gdata_Spreadsheets_ListEntry ) {
                try{ $response = $new_entry->save(); }
                catch (Exception $e) { die("got exception!: " . $e->getMessage()); return -1; }
            }
        }
        return 0;
    }

    public function getUniqueRow($key, $text)
    {
        $query = $this->createListQuery();
        $query->spreadsheetQuery = "$key=$text";
        try{ $listFeed = $this->gdClient->getListFeed($query); }
        catch (Exception $e){ die( "caught exception: " . $e->getMessage() ); return null; }

        if( $listFeed == null || !isset($listFeed->entries[0]) ) {
            return null;
        }

        $rowData = $listFeed->entries[0]->getCustom();
        $row = array();
        foreach($rowData as $customEntry) {
            $row = array_merge( $row,
                                array($customEntry->getColumnName() => $customEntry->getText()) );
        }

        return $row;
    }

    public function getUniqueRows($key, $text)
    {
        $query = $this->createListQuery();
        $query->spreadsheetQuery = "$key=$text";
        try {
            $listFeed = $this->gdClient->getListFeed($query);
        } catch (Exception $e) {
            die( "caught exception: " . $e->getMessage() );
            return null;
        }

        if( $listFeed == null || !isset($listFeed->entries[0]) ) {
            return null;
        }

        $rows = array();
        foreach($listFeed->entries as $rawRowData) {
            $rowData = $rawRowData->getCustom();
            $row = array();
            foreach($rowData as $customEntry) {
                $row = array_merge( $row,
                                    array($customEntry->getColumnName() => $customEntry->getText()) );
            }
            $rows = array_merge( $rows, array($row) );
        }

        return $rows;
    }

    private function createListQuery()
    {
        $query = new Zend_Gdata_Spreadsheets_ListQuery();
        $query->setSpreadsheetKey($this->currKey);
        $query->setWorksheetId($this->currWkshtId);
        return $query;
    }

    private function getIndexOf($item, $feed)
    {
        $i = 0;
        foreach($feed->entries as $entry) {
            if( $entry->title->text == $item )
                return $i;
            $i++;
        }
    }

    private function printFeed($feed)
    {
        $i = 0;
        foreach($feed->entries as $entry) {
            if ($entry instanceof Zend_Gdata_Spreadsheets_CellEntry)
                print $entry->title->text . ' ' . $entry->content->text . "<br/>";
            else if ($entry instanceof Zend_Gdata_Spreadsheets_ListEntry)
                print $i . ' ' . $entry->title->text . ' | ' . $entry->content->text . "<br/>";
            else {
                print $i . ' ' . $entry->title->text . "foo<br/>";
            }
            $i++;
        }
    }
}

?>
