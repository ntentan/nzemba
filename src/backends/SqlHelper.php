<?php

namespace ntentan\nzemba\backends;

interface SqlHelper
{
    public function quoteIdentifier(string $identifier): string;
}
