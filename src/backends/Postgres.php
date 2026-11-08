<?php

namespace ntentan\nzemba\backends;

class Postgres extends SqlDatabase
{
    public function quoteIdentifier($identifier)
    {
        return "\"$identifier\"";
    }
}
