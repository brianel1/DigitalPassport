<?php

// Review reports stay local and are not present in a fresh checkout.
$reviewOutputDirectory=__DIR__.'/../design-system/skills-career';
if (!is_dir($reviewOutputDirectory) && !mkdir($reviewOutputDirectory,0775,true) && !is_dir($reviewOutputDirectory)) {
    throw new RuntimeException('Cannot create local review report directory.');
}
