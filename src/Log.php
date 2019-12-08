<?

namespace BitrixNovaPoshta;

class Log
{
    public function __construct(array $data, $name)
    {
        $logFileName = dirname(__DIR__) . "/logs/" . date('Y-m-d_H-i') . ".txt";
        if ($fp = @fopen($logFileName, "ab")) {
            if (flock($fp, LOCK_EX)) {
                @fwrite($fp, $name . ": \n");
                @fwrite($fp, "\nCount created: " . count($data['created']));
                @fwrite($fp, "\nCount updated: " . count($data['updated']));
                @fwrite($fp, "\nCount deprecated: " . count($data['deprecated']));
                @fwrite($fp, "\n" . print_r($data, true));
                @fflush($fp);
                @flock($fp, LOCK_UN);
                @fclose($fp);
            }
        }
    }
}
