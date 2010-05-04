<?php

$block_name = _("Most Popular Stories");

/**
 * This class extends Horde_Block:: to provide an api to embed news
 * in other Horde applications.
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 *
 * @package Horde_Block
 */
class Horde_Block_Jonah_news_popular extends Horde_Block {

    var $_app = 'jonah';

    function _params()
    {
        require JONAH_BASE . '/config/templates.php';

        $params['source'] = array('name' => _("Feed"),
                                  'type' => 'enum',
                                  'values' => array());

        $news = Jonah_News::factory();
        $channels = $news->getChannels();
        foreach ($channels as  $channel) {
            if ($channel['channel_type'] == Jonah::INTERNAL_CHANNEL) {
                $params['source']['values'][$channel['channel_id']] = $channel['channel_name'];
            }
        }
        natcasesort($params['source']['values']);

        $params['view'] = array('name' => _("View"),
                                'type' => 'enum',
                                'values' => array());
        foreach ($templates as $key => $template) {
            $params['view']['values'][$key] = $template['name'];
        }

        $params['max'] = array('name' => _("Maximum Stories"),
                               'type' => 'int',
                               'default' => 10,
                               'required' => false);

        return $params;
    }

    function _title()
    {
        $news = Jonah_News::factory();
        $channel = $news->getChannel($this->_params['source']);
        if (is_a($channel, 'PEAR_Error')) {
            return @htmlspecialchars($channel->getMessage(), ENT_COMPAT, Horde_Nls::getCharset());
        }

        if (!empty($channel['channel_link'])) {
            $title = Horde::link(htmlspecialchars($channel['channel_link']), '', '', '_blank')
                . @htmlspecialchars($channel['channel_name'], ENT_COMPAT, Horde_Nls::getCharset())
                . _(" - Most read stories") . '</a>';
        } else {
            $title = @htmlspecialchars($channel['channel_name'], ENT_COMPAT, Horde_Nls::getCharset())
                . _(" - Most read stories");
        }

        return $title;
    }

    function _content()
    {
        if (empty($this->_params['source'])) {
            return _("No feed specified.");
        }

        $news = Jonah_News::factory();
        $params = $this->_params();

        $view = isset($this->_params['view']) ? $this->_params['view'] : 'standard';
        if (!isset($this->_params['max'])) {
            $this->_params['max'] = $params['max']['default'];
        }


        return $news->renderChannel($this->_params['source'], $view, $this->_params['max'], 0, Jonah::ORDER_READ);
    }

}
