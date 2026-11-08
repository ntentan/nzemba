<?php

namespace ntentan\nzemba\backends;

class Postgres implements SqlHelper
{
    public function quoteIdentifier(string $identifier): string
    {
        return "\"$identifier\"";
    }
}
