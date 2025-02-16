<?php

require_once(__DIR__ . '/../Model/LinkModel.php');

class LinkController {
    private $linkModel;

    public function __construct() {
        $this->linkModel = new LinkModel();
    }

    public function validLink($token) {
        return $this->linkModel->validLink($token);
    }
}