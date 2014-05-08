<?php
/*
====================================================================================================
 Author: Peter Lewis - peter@peteralewis.com
 http://www.peteralewis.com
====================================================================================================
 This file must be placed in the /system/expressionengine/third_party/store_options folder
 package            Tax
 version            Version 1.0.0
 copyright          Copyright (c) 2013 Peter Lewis
 license            Attribution No Derivative Works 3.0: http://creativecommons.org/licenses/by-nd/3.0/
 Last Update        April 2013
----------------------------------------------------------------------------------------------------
 Purpose: Converts prices to do all taxing stuff: show tax only, add tax or remove tax
====================================================================================================

Change Log

v1.0.0	Initial Version


*/

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
                    'pi_name'           => 'Tax',
                    'pi_version'        => '1.0.0',
                    'pi_author'         => 'Peter Lewis',
                    'pi_author_url'     => 'http://www.peteralewis.com/',
                    'pi_description'    => 'Converts prices to do all taxing stuff: show tax only, add tax or remove tax.',
                    'pi_usage'          => Tax::usage()
);

class Tax {
    public $return_data;
    var $site_id, $EE;
    var $rate, $symbol, $showSymbol, $position, $price, $taxIncluded, $showDecimals, $showThousands, $rounding;
    var $decimalPlaces, $thousandSymbol, $decimalSymbol;
    const BEFORE = 1;
	const AFTER = 2;
    const ROUNDUP = 1;
    const ROUNDDOWN = 2;
    const ROUND = 3;

    //###   Constructor   ###
    public function __construct() {
        //###   Get EE Super Global   ###
        $this->EE =& get_instance();

        //###   General Variables   ###
        $this->site_id = $this->EE->config->item('site_id');

        $this->getConfigVars();
    }//###   End of __construct function


    //###   Show only tax for given price   ###
    public function only() {
        if ($this->parameters() === false)
            return $this->EE->TMPL->no_results();

        if ($this->taxIncluded) {
            $output = $this->price - ( $this->price / (($this->rate/100)+1) );
        } else {
            $output = ($this->price/100) * $this->rate;
        }

        //###   Round Tax Up to 2 decimal places and net down - can't rely on round, e.g.: total=3.75, tax@20%=0.625, net=3.125 (so tax+net=3.74)   ###
        $output = ceil(100*$output)/100;

        $output = $this->tidy($output);

        return $output;
	} //###   End of Only function


    //###   Show price with tax taken off (assumes given price is inclusive)   ###
    function none() {
        if ($this->parameters() === false)
            return $this->EE->TMPL->no_results();

        if ($this->taxIncluded) {
            $output = $this->price / (($this->rate/100)+1);
        }

        //###   Round Net Down to 2 decimal places and tax up - can't rely on round, e.g.: total=3.75, tax@20%=0.625, net=3.125 (so tax+net=3.74)   ###
        $output = floor(100*$output)/100;

        $output = $this->tidy($output);

        return $output;
	} //###   End of None function


    function price() {
        if ($this->parameters() === false)
            return $this->EE->TMPL->no_results();

        if ($this->taxIncluded) {
            $output = $this->price;
        } else {
            $output = $this->price + (($this->price/100) * $this->rate);
        }

        return $output;
    } //###   End of Price function


    function round() {
        if ($this->parameters() === false)
            return $this->EE->TMPL->no_results();

        $output = $this->rounding($this->price);
        return $output;
    } //###   End of Price function


    //###   Show price with tax based on a given tax value (price unknown)   ###
    function reverse() {

	} //###   End of Reverse function


    //###   Show price without tax based on a given tax value (price unknown)   ###
    function reverseless() {

	} //###   End of Reverseless function


    private function getConfigVars() {
        //###   Custom Config Variables   ###
        if (isset($this->EE->config->_global_vars['tax_rate']))
            $this->rate = preg_replace("/[^0-9.]/", '', $this->EE->config->_global_vars['tax_rate']);
        $this->taxIncluded = $this->EE->config->_global_vars['tax_included'];
        $this->symbol = $this->EE->config->_global_vars['tax_currency'];
        $this->position = $this->EE->config->_global_vars['tax_position'];
        if (isset($this->EE->config->_global_vars['tax_decimals']))
            $this->showDecimals = $this->check_boolean($this->EE->config->_global_vars['tax_decimals']);
        if (isset($this->EE->config->_global_vars['tax_thousands']))
            $this->showThousands = $this->check_boolean($this->EE->config->_global_vars['tax_thousands']);
        if (isset($this->EE->config->_global_vars['tax_show_currency']))
            $this->showSymbol = $this->check_boolean($this->EE->config->_global_vars['tax_show_currency']);
        if (isset($this->EE->config->_global_vars['tax_rounding']))
            $this->rounding = $this->EE->config->_global_vars['tax_rounding'];

        if (isset($this->EE->config->_global_vars['tax_decimal_places']))
            $this->decimalPlaces = $this->EE->config->_global_vars['tax_decimal_places'];
        else
            $this->decimalPlaces = 2;

        if (isset($this->EE->config->_global_vars['tax_thousand_symbol']))
            $this->thousandSymbol = $this->EE->config->_global_vars['tax_thousand_symbol'];
        else
            $this->thousandSymbol = ",";

        if (isset($this->EE->config->_global_vars['tax_decimal_symbol']))
            $this->decimalSymbol = $this->EE->config->_global_vars['tax_decimal_symbol'];
        else
            $this->decimalSymbol = ".";

        return;
    } //###   End of getConfigVars function

    private function tidy($price) {
        if (!empty($this->rounding))
            $price = $this->rounding($price);

        if ($this->showDecimals)
            $price = number_format($price, $this->decimalPlaces, $this->decimalSymbol, $this->thousandSymbol);
		else
			$price = intval($price);

        if ($this->showSymbol && !empty($this->symbol)) {
            if ($this->position == self::AFTER)
                $price = $price.$this->symbol;
            else
                $price = $this->symbol.$price;
        }

        return $price;
    } //###   End of Tidy function

    //###   Round
    private function rounding($price) {
    	//###   Rounding Rules   ###
        if ($this->rounding == self::ROUNDUP)
            $price = ceil($price);
        else if ($this->rounding == self::ROUNDDOWN)
            $price = floor($price);
        else if ($this->rounding == self::ROUND)
            $price = round($price, $this->decimalPlaces);

        return $price;
    } //###   End of Rounding function


    //###   Get Parameters used on plugin call   ###
    private function parameters() {
        //###   Get price parameter   ###
        $rawPrice = $this->EE->TMPL->fetch_param('price');
        if (empty($rawPrice))
            $rawPrice = $this->EE->TMPL->fetch_param('value');
        $this->price = preg_replace("/[^0-9.]/", '', $rawPrice);

        //###   Check for tag pair   ###
        if (!empty($this->EE->TMPL->tagdata)) {
            $rawPrice = $this->EE->TMPL->tagdata;
            $this->price = preg_replace("/[^0-9.]/", '', $rawPrice);
        }

        if (empty($this->price) && $this->price != "0") {
            $this->log("FATAL: No price passed as a parameter or tag pair :(");
            return false;
        }

        //###   Check if currency symbol passed in value   ###
        $assumedSymbol = preg_replace("/[0-9-.,]/", '', $rawPrice);

        //###   Tax rate   ###
        $param = $this->EE->TMPL->fetch_param('rate');
        if (empty($param))
            $param = $this->EE->TMPL->fetch_param('tax');
        $param = preg_replace("/[^0-9.]/", '', $param);
        if (!empty($param))
            $this->rate = $param;

        if (empty($this->rate)) {
            $this->rate = 0;
            $this->log("WARNING: Tax rate set to 0, not sure if that was intended...?");
        }

        $param = $this->EE->TMPL->fetch_param('show_currency');
        if (!empty($param))
            $this->showSymbol = $this->check_boolean($param);

        //###   Symbol to use   ###
        $overrideSymbol = $this->EE->TMPL->fetch_param('currency', false);
        if (!empty($overrideSymbol))
            $this->symbol = $overrideSymbol;
        else if (!empty($assumedSymbol))
            $this->symbol = $assumedSymbol;

        if (!empty($this->symbol))
            $this->showSymbol = true;

        //###   Position to place symbol   ###
        $param = strtolower($this->EE->TMPL->fetch_param('position', $this->position));
        if ($param == "post" || $param == "after")
            $this->position = self::AFTER;
        else if ($param == "pre" || $param == "before")
            $this->position = self::BEFORE;
        else
            $this->position = self::BEFORE;

        $param = $this->EE->TMPL->fetch_param('decimals');
        if (!empty($param))
            $this->showDecimals = $this->check_boolean($param);
        else if (empty($this->showDecimals))
            $this->showDecimals = true;

        $param = $this->EE->TMPL->fetch_param('thousands');
        if (!empty($param))
            $this->showThousands = $this->check_boolean($param);
        else if (empty($this->showThousands))
            $this->showThousands = true;
        if (!$this->showThousands)
            $this->thousandSymbol = "";

        $param = $this->EE->TMPL->fetch_param('included');
        if (!empty($param))
            $this->taxIncluded = $this->check_boolean($param);
        else if (empty($this->taxIncluded))
            $this->taxIncluded = true;

        $param = strtolower($this->EE->TMPL->fetch_param('rounding', $this->rounding));
        if (!empty($param)) {
            if ($param == "up")
                $this->rounding = self::ROUNDUP;
            else if ($param == "down")
                $this->rounding = self::ROUNDDOWN;
            else if ($param == "round" || $param == "nearest")
                $this->rounding = self::ROUND;
        }

        return true;
    } //###   End of Parameters function


	private function check_boolean($var = false, $check = true) {
		$returnVal = false;
		if ($check !== false)
			$check = true;

		if (empty($var)) {
			//###   No variable has been passed or is empty, so will return default (false), unless checking for false   ###
			if (!$check)
				$returnVal = true;

		} else {
			if ($check)
				if (strtolower($var) === "true" || strtolower($var) === "t" || strtolower($var) === "yes" || strtolower($var) === "y" || $var === "1")
					$returnVal = true;
			else
				if (strtolower($var) === "false" || strtolower($var) === "f" || strtolower($var) === "no" || strtolower($var) === "n" || $var === "0")
					$returnVal = true;
		}

		return $returnVal;
	} //###   End of check_boolean function


    private function log($message) {
        $callers = debug_backtrace();
        $this->EE->TMPL->log_item(__CLASS__." (".$callers[1]['function']."):  ".$message );
    }//###   End of log function


    // ----------------------------------------
    //  Plugin Usage
    // ----------------------------------------
    // This function describes how the plugin is used.
    //  Make sure and use output buffering

    function usage() {
        ob_start();
    ?>


    Default variables can be set in your EE config file:
        tax_rate
        tax_included
        tax_currency
        tax_position
        tax_decimals
        tax_thousands
        tax_show_currency
        tax_rounding
        tax_decimal_places
        tax_thousand_symbol
        tax_decimal_symbol

    Full documentation can be found here: http://peteralewis.com/expressionengine/tax/docs
    Support and help can be found here: http://peteralewis.com/support/tax

    <?php
        $buffer = ob_get_contents();

        ob_end_clean();

        return $buffer;
    } /* ###   End of usage() Function   ### */

}  /* ###   End of Class   ### */