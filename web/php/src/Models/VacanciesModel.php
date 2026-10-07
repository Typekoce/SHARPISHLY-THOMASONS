<?php

namespace App\Models;

class VacanciesModel extends BaseModel {

    public function cleanHtml($file){

        $file = strip_tags($file);

        return $file;

    }
}