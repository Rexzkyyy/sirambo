<?php

function fmt($angka, $desimal = 2)
{
    return rtrim(
        rtrim(
            number_format($angka, $desimal, ',', '.'),
            '0'
        ),
        ','
    );
}
