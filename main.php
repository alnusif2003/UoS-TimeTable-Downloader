<?php

include 'simple_html_dom.php';
include 'connection.php';


set_error_handler(
    function ($severity, $message, $file, $line) {
        throw new ErrorException($message, $severity, $severity, $file, $line);
    }
);
date_default_timezone_set('Europe/London');

mysqli_query($con, "CREATE TABLE IF NOT EXISTS uosTimeTables (id INT PRIMARY KEY NOT NULL AUTO_INCREMENT, name VARCHAR(100) DEFAULT NULL);");



function getStringBetween($string, $start, $end)
{
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

function fetchContent($url, $fileName)
{
    try {


        $data = file_get_contents($url);
        $html = str_get_html($data);


        foreach ($html->find('td') as $element) {
            if ($element->colspan === '2') {
                $subjects[] = $element->innertext . ' 0.5 hour class session';
            } else if ($element->colspan === '6') {
                $subjects[] = $element->innertext . ' 1.5 hour class session';
            } else if ($element->colspan === '8') {
                $subjects[] = $element->innertext . ' 2 hour class session';
                $subjects[] = '';
            } else if ($element->colspan === '10') {
                $subjects[] = $element->innertext . ' 2.5 hour class session';
                $subjects[] = '';
            } else if ($element->colspan === '12') {
                $subjects[] = $element->innertext . ' 3 hour class session';
                $subjects[] = '';
                $subjects[] = '';
            } else if ($element->colspan === '14') {
                $subjects[] = $element->innertext . ' 3.5 hour class session';
                $subjects[] = '';
                $subjects[] = '';
            } else if ($element->colspan === '16') {
                $subjects[] = $element->innertext . ' 4 hour class session';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
            } else if ($element->colspan === '18') {
                $subjects[] = $element->innertext . ' 4.5 hour class session';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
            } else if ($element->colspan === '20') {
                $subjects[] = $element->innertext . ' 5 hour class session';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
            } else if ($element->colspan === '22') {
                $subjects[] = $element->innertext . ' 5.5 hour class session';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
            } else if ($element->colspan === '24') {
                $subjects[] = $element->innertext . ' 6 hour class session';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
            } else if ($element->colspan === '26') {
                $subjects[] = $element->innertext . ' 6.5 hour class session';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
            } else if ($element->colspan === '28') {
                $subjects[] = $element->innertext . ' 7 hour class session';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
            } else if ($element->colspan === '30') {
                $subjects[] = $element->innertext . ' 7.5 hour class session';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
            } else if ($element->colspan === '32') {
                $subjects[] = $element->innertext . ' 8 hour class session';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
            } else if ($element->colspan === '34') {
                $subjects[] = $element->innertext . ' 7.5 hour class session';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
            } else if ($element->colspan === '36') {
                $subjects[] = $element->innertext . ' 8 hour class session';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
                $subjects[] = '';
            } else {
                $subjects[] = $element->innertext;
            }
            if ($element->colspan === '4' && $element->innertext !== '') {
                $times[] = $element->innertext;
            }
        }
    } catch (Exception) {
        exit("Invalid URL");
    }

    try {

        $times = array_slice($times, 0, 8);
        $subjects = array_slice($subjects, 42);
        unset($subjects[8]);
        unset($subjects[17]);
        unset($subjects[26]);
        unset($subjects[35]);
        $subjects = array_values($subjects);
    } catch (Exception) {
        exit('Invalid Time Table');
    }

    return array($times, $subjects);
}
function parse($times, $subjects)
{
    $count = 0;
    $daysCount = 0;
    $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday');
    $output = array();



    while ($count != 40) {
        $day = $days[$daysCount];
        $tmpArray = array(
            "$day" => array()
        );
        foreach ($times as $time) {
            try {
                $subject = $subjects[$count];
            } catch (Exception) {
                exit("Invalid Time Table");
            }
            if ($subject != '') {
                $startTime = explode(' - ', $time)[0];
                $endTime = explode(' - ', $time)[1];
                $classLength = explode(' ',  substr($subject, 0, -19));
                $classLength = array_pop($classLength);
                // check if class is over 1 hour long and increase time accordingly
                if (str_contains($subject, " $classLength hour class session")) {
                    $endTime = strtotime($endTime) + 60 * 60 * ($classLength - 1);
                    $endTime = date('H:i', $endTime);
                    $time = $startTime . ' - ' . $endTime;
                    $subject = str_replace(" $classLength hour class session", '', $subject);
                }
                $description = explode('</b> ', $subject)[1] ?? 'Unavailable';
                $subject = getStringBetween($subject, '<b>', '</b>');

                $data =
                    array(
                        "start" => "$startTime",
                        "end" => "$endTime",
                        "subject" => "$subject",
                        "description" => "$description"
                    );
                if ($description != 'Unavailable') {
                    array_push($tmpArray[$day], $data);
                }
            }
            $count++;
            if ($count % 8 == 0) {
                $daysCount++;
            }
        }
        array_push($output, $tmpArray);
    }
    $output = json_encode($output);
    return $output;
}

function addDate($timetable, $startDate)
{

    $timetable = json_decode($timetable, true);
    $count = 0;
    $currentWeek = 1;
    $endDate = DateTime::createFromFormat('m/d/Y', $startDate);
    $endDate->add(DateInterval::createFromDateString('50 weeks'));
    $endDate = $endDate->format('m/d/Y');
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $oneday = new DateInterval("P1D");
    $days = array();
    $output = array();
    $weeksOfClass = array();

    // get all days between start and end
    foreach (new DatePeriod($start, $oneday, $end->add($oneday)) as $day) {
        $day_num = $day->format("N");
        if ($day_num < 6) {
            array_push($days, $day->format("Y-m-d"));
        }
    }
    foreach ($days as $day) {
        $dayName = strtolower(date('l', strtotime($day)));
        $dayTable = $timetable[$count];

        if ($dayTable[$dayName] != null) {
            foreach ($dayTable[$dayName] as $key => $value) {
                $classWeeks = explode(',<wbr>', getStringBetween($dayTable[$dayName][$key]['description'], '(weeks ', ')'));
                foreach ($classWeeks as $classWeek) {
                    if (str_contains($classWeek, '-')) {
                        $classWeek = explode('-', $classWeek);
                        $classWeek = range($classWeek[0], $classWeek[1]);
                        array_push($weeksOfClass, $classWeek);
                    } else {
                        array_push($weeksOfClass, array($classWeek));
                    }
                }

                if (is_array($weeksOfClass[0])) {
                    $weeksOfClass = array_unique(call_user_func_array('array_merge', $weeksOfClass));
                }
                if (in_array($currentWeek, $weeksOfClass)) {
                    // gmdate('Y-m-d H:i', strtotime($date));
                    $dayTable[$dayName][$key]['start'] = gmdate('Y-m-d H:i', strtotime($day . ' ' . $dayTable[$dayName][$key]['start']));
                    $dayTable[$dayName][$key]['end'] = gmdate('Y-m-d H:i', strtotime($day . ' ' . $dayTable[$dayName][$key]['end']));
                    $dayTable[$dayName][$key]['description'] = str_replace('<wbr>', ' ', $dayTable[$dayName][$key]['description']);
                    $dayTable[$dayName][$key]['location'] = getStringBetween($dayTable[$dayName][$key]['description'], ' in ', ' (');
                    $dayTable[$dayName][$key]['description'] = substr($dayTable[$dayName][$key]['description'], 0, strpos($dayTable[$dayName][$key]['description'], ' (week'));
                    array_push($output, $dayTable[$dayName][$key]);
                }
                $weeksOfClass = array();
            }
        }
        $count++;
        if ($count == 5) {
            $count = 0;
            $currentWeek++;
        }
    }
    $output = json_encode($output);
    return $output;
}


function dataToIcs($data, $fileName)
{
    $ics = "";
    $data = json_decode($data, true);
    foreach ($data as $key => $value) {
        $ics .= "BEGIN:VCALENDAR\nVERSION:2.0\nMETHOD:PUBLISH\nBEGIN:VEVENT\nDTSTART:" . date("Ymd\THis\Z", strtotime($value['start'])) . "\nDTEND:" . date("Ymd\THis\Z", strtotime($value['end'])) . "\nLOCATION:" . $value['location'] . "\nTRANSP: OPAQUE\nSEQUENCE:0\nUID:\nDTSTAMP:" . date("Ymd\THis\Z") . "\nSUMMARY:" . $value['subject'] . "\nDESCRIPTION:" . $value['description'] . "\nPRIORITY:1\nCLASS:PUBLIC\nBEGIN:VALARM\nTRIGGER:-PT10080M\nACTION:DISPLAY\nDESCRIPTION:Reminder\nEND:VALARM\nEND:VEVENT\nEND:VCALENDAR\n";
    }
    header('Content-type: text/calendar; charset=utf-8');
    header("Content-Disposition: inline; filename=$fileName.ics");
    file_put_contents("timetables/$fileName.ics", $ics);
    return "/timetables/$fileName.ics";
}


function getCalendars($url, $startDate)
{
    global $con;

    if (str_starts_with($url, 'https://firstyearmatters.info/timetables/') || str_starts_with($url, 'http://firstyearmatters.info/timetables/')) {
        $fileName = getStringBetween($url, 'timetables/', '.html');
        $lines = mysqli_num_rows(mysqli_query($con, "SELECT name FROM uosTimeTables WHERE name='$fileName'"));
        // check if timetable already exists
        if ($lines == 0) {
            $startDate = preg_replace("/(\d+)\D+(\d+)\D+(\d+)/", "$2/$1/$3", $startDate);
            list($times, $subjects) = fetchContent($url, $fileName);
            $data = parse($times, $subjects);
            $data = addDate($data, $startDate);
            $path = dataToIcs($data, $fileName);
            mysqli_query($con, "INSERT INTO uosTimeTables (name) VALUES ('$fileName')");
        } else {
            $path = "/timetables/$fileName.ics";
        }
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            $fullPath = "https://";
        else
            $fullPath = "http://";
        if (isset($_SERVER['HTTP_HOST'])) {
            $fullPath .= $_SERVER['HTTP_HOST'];
            $fullPath .= $_SERVER['REQUEST_URI'];
            $fullPath .= $fullPath . $path;
        } else {
            $fullPath .= 'localhost' . $path;
        }

        $calendars = array(
            'custom' => $fullPath,
            'google' => "https://calendar.google.com/calendar/u/0/r?cid=$fullPath&pli=1",
            'yahoo' => "https://calendar.yahoo.com/subscribe?ics=$fullPath&name=$fileName",
            'default' => "webcal://$fullPath"
        );

        return $calendars;
    } else {
        echo 'Invalid URL';
    }
}
