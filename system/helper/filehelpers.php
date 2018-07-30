<?Php

namespace resgef\paulebay\helper\filehelpers;

function append_to_filename($file, $part) {
    return \pathinfo($file, PATHINFO_FILENAME).$part.'.'.\pathinfo($file, PATHINFO_EXTENSION);
}