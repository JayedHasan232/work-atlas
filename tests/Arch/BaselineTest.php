<?php

arch()->preset()->php();

arch()->preset()->security();

arch()->preset()->laravel();

arch('debugging calls are not left in the code')
    ->expect(['dd', 'ddd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();
