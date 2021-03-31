<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

/**
 * @package   block_shop_bills
 * @category  blocks
 * @author    Valery Fremaux (valery.fremaux@gmail.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_shop_bills extends block_list {

    public function init() {
        $this->title = get_string('blockname', 'block_shop_bills');
        $this->version = 2013022200;
    }

    public function applicable_formats() {
        return array('all' => false, 'my' => true, 'course' => true);
    }

    public function specialization() {
        if (!empty($this->config) && !empty($this->config->title)) {
            $this->title = format_string($this->config->title);
        }
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function get_content() {
        global $USER, $DB;

        if ($this->content !== null) {
            return $this->content;
        }
        if (!isset($this->config) || empty($this->config->shopinstance)) {
            $this->content = new stdClass;
            $this->content->icons[] = '';
            $this->content->items[] = get_string('notconfigured', 'block_shop_bills');
            $this->content->footer = '';
            return $this->content;
        }

        $this->content = new stdClass;
        $sql = "
            SELECT
                b.*
            FROM
                {local_shop_bill} b,
                {local_shop_customer} c
            WHERE
                b.customerid = c.id AND
                c.hasaccount = ? AND
                b.status IN ('PLACED', 'SOLDOUT', 'PREPROD', 'PENDING', 'COMPLETE', 'PARTIAL') AND
                shopid = ?
            ORDER BY
                emissiondate
        ";

        if ($invoices = $DB->get_records_sql($sql, [$USER->id, $this->config->shopinstance])) {
            foreach ($invoices as $invoice) {
                $invoicedate = date('Y/m/d H:i', $invoice->emissiondate);
                $invoicestr = $invoice->title;
                $params = array('view' => 'bill', 'shopid' => $this->config->shopinstance, 'transid' => $invoice->transactionid);
                $billurl = new moodle_url('/local/shop/front/view.php', $params);
                $amount = sprintf('%0.2f', round($invoice->amount, 2));
                $this->content->items[] = $invoicedate.' <a href="'.$billurl.'">'.$invoicestr.'</a> ('.$amount.')';
                $this->content->icons[] = '';
            }
        } else {
            $this->content->icons[] = '';
            $this->content->items[] = get_string('nobills', 'block_shop_bills');
        }

        $this->content->footer = '';

        return $this->content;
    }

    /*
     * Hide the title bar when none set..
     */
    public function hide_header() {
        return empty($this->config->title);
    }
}
