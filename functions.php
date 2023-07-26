<?php

function formatPrice($vlprice){

    if ($vlprice > 0 ) {
        return number_format($vlprice, 2, ",", ".");
    }
    

}

?>