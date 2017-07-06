<?

class UserTextsProcessing {

    private $delimiter;

    /**
     * @var resource
     */
    private $handle;

    public function __construct($filename, $delimiter = ',') {
        $this->delimiter = $delimiter;

        $this->handle = fopen($filename, "r");
        if (FALSE === $this->handle) {
            throw new Exception('Could not open the file.');
        }
    }

    public function __destruct() {
        fclose($this->handle);
    }

    private function getUsers() {
        $users = [];

        while (
        ($data = fgetcsv($this->handle, 0, $this->delimiter)) !== FALSE
        ) {
            $users[] = [
                'id' => (int) $data[0],
                'name' => $data[1],
            ];
        }

        return $users;
    }

    private function getUserFiles($user_id) {
        return glob(
                './texts/' . $user_id . '-*.txt', GLOB_NOSORT
        );
    }

    private function getAverageLineCountByUser($user_id) {
        $user_files = $this->getUserFiles($user_id);
        $count_user_files = count($user_files);

        if (!$count_user_files) {
            return null;
        }

        $line_counts_sum = 0;
        foreach ($user_files as $file) {
            $line_counts_sum += count(file($file));
        }

        return $line_counts_sum / $count_user_files;
    }

    public function getAverageLineCounts() {
        $result = [];
        foreach ($this->getUsers() as $user) {
            $result[] = [
                'user' => $user,
                'averageLineCount' => $this->getAverageLineCountByUser($user['id']),
            ];
        }

        return $result;
    }

}

//можно передавать два параметра:
//
//Тип разделителя для CSV файлов (текстовая строка без кавычек)
//    comma для запятой
//    semicolon для точки с запятой)
//
//Тип задачи, которую требуется выполнить над текстами пользователей (текстовая строка без кавычек)
//
//    countAverageLineCount - для каждого пользователя посчитать среднее количество строк в его текстовых файлах
//        и вывести на экран вместе с именем пользователя.
//
//    replaceDates - поместить тексты пользователей в папку ./output_texts,
//        заменив в каждом тексте даты в формате dd/mm/yy на даты в формате mm-dd-yyyy.
//        Вывести на экран количество совершенных для каждого пользователя замен вместе с именем пользователя.
//
//Пользователь может вызвать утилиту, например, так:
//php user_text_util.php comma countAverageLineCount

$peoples_file = './people.csv';

$delimiter_mapping_word2char = [
    'comma' => ',',
    'semicolon' => ';',
];
$task_mapping_word2method = [
    'countAverageLineCount' => 'getAverageLineCounts',
];

if ($argc < 3){
    echo 'Specify a delimiter ("', implode('", "', array_keys($delimiter_mapping_word2char)), '"',
        ') and a task ("', implode('", "', array_keys($task_mapping_word2method)), '"',
        ")\n"
    ;

    // @todo: consider if it should return error code (other than 0)?
    return;
}
$delimiter_word = $argv[1];
$task = $argv[2];

$csv_processing = new UserTextsProcessing($peoples_file, $delimiter_mapping_word2char[$delimiter_word]);

$result = $csv_processing->{$task_mapping_word2method[$task]}();

printResult($result, 'averageLineCount');

function printResult($data, $field) {
    foreach ($data as $row) {
        echo $row['user']['name'],
        ': ',
        $row[$field] !== null ? $row[$field] : '0 (no files)',
        "\n"
        ;
    }
}
