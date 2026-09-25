<?php

namespace ntentan\nzemba\generators;

class Postgres extends Generator
{
    public function quoteIdentifier($identifier)
    {
        return "\"$identifier\"";
    }
}