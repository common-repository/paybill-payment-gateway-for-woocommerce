<?php
/**
 * Created by PhpStorm.
 * User: kobi
 * Date: 21/04/2017
 * Time: 12:06 PM
 */
class paybillng_pages_to_add
{

    public static function paybillng_pages(){

        $pages_definitions = array(
            'processing-paybillng' => array(
                'title' => __( 'Processing-PaybillNG', 'paybills' ),
                'content' => '[paybill-process-payment]'
            )
        );

        return $pages_definitions;
    }

}