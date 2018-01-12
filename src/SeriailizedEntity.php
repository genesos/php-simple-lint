<?php

namespace SimpleLint;

class SeriailizedEntity
{
    public $startLine;
    public $file_pos;
    public $clause;
    public $type;
    public $reason;

    /**
     * SeriailizedEntity constructor.
     *
     * @param $startLine
     * @param $file_pos
     * @param $serialized_string
     * @param $type
     */
    public function __construct($startLine, $file_pos, $serialized_string, $type)
    {
        $this->startLine = $startLine;
        $this->file_pos = $file_pos;
        $this->clause = $serialized_string;
        $this->type = $type;
    }
}
