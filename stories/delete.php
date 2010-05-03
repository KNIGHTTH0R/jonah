<?php
/**
 * $Horde: jonah/stories/delete.php,v 1.40 2009/11/24 04:15:38 chuck Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Marko Djukic <marko@oblo.com>
 */

define('JONAH_BASE', dirname(__FILE__) . '/..');
require_once JONAH_BASE . '/lib/base.php';
require_once JONAH_BASE . '/lib/News.php';
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';

/* Set up the form variables. */
$vars = Horde_Variables::getDefaultVariables();
$form_submit = $vars->get('submitbutton');
$channel_id = $vars->get('channel_id');
$story_id = $vars->get('story_id');

/* Driver */
$news = Jonah_News::factory();

/* Fetch the channel details, needed for later and to check if valid
 * channel has been requested. */
$channel = $news->isChannelEditable($channel_id);
if (is_a($channel, 'PEAR_Error')) {
    $notification->push(sprintf(_("Story editing failed: %s"), $channel->getMessage()), 'horde.error');
    $url = Horde::applicationUrl('channels/index.php', true);
    header('Location: ' . $url);
    exit;
}

/* Check permissions. */
if (!Jonah::checkPermissions(Jonah::typeToPermName($channel['channel_type']), Horde_Perms::DELETE, $channel_id)) {
    $notification->push(_("You are not authorised for this action."), 'horde.warning');
    Horde::authenticationFailureRedirect();
}

$story = $news->getStory($channel_id, $story_id);
if (is_a($story, 'PEAR_Error')) {
    $notification->push(_("No valid story requested for deletion."), 'horde.message');
    $url = Horde::applicationUrl('channels/index.php', true);
    header('Location: ' . $url);
    exit;
}

/* If not yet submitted set up the form vars from the fetched story. */
if (empty($form_submit)) {
    $vars = new Horde_Variables($story);
}

$title = sprintf(_("Delete News Story \"%s\"?"), $vars->get('story_title'));

$form = new Horde_Form($vars, $title);

$form->setButtons(array(_("Delete"), _("Do not delete")));
$form->addHidden('', 'channel_id', 'int', true, true);
$form->addHidden('', 'story_id', 'int', true, true);
$form->addVariable(_("Really delete this News Story?"), 'confirm', 'description', false);

if ($form_submit == _("Delete")) {
    if ($form->validate($vars)) {
        $form->getInfo($vars, $info);
        $delete = $news->deleteStory($info['channel_id'], $info['story_id']);
        if (is_a($delete, 'PEAR_Error')) {
            $notification->push(sprintf(_("There was an error deleting the story: %s"), $delete->getMessage()), 'horde.error');
        } else {
            $notification->push(_("The story has been deleted."), 'horde.success');
            $url = Horde::applicationUrl('stories/index.php', true);
            $url = Horde_Util::addParameter($url, 'channel_id', $channel_id, false);
            header('Location: ' . $url);
            exit;
        }
    }
} elseif (!empty($form_submit)) {
    $notification->push(_("Story has not been deleted."), 'horde.message');
    $url = Horde::applicationUrl('stories/index.php', true);
    $url = Horde_Util::addParameter($url, 'channel_id', $channel_id, false);
    header('Location: ' . $url);
    exit;
}

$template = new Horde_Template();
$template->set('main', Horde_Util::bufferOutput(array($form, 'renderActive'), new Horde_Form_Renderer(), $vars, 'delete.php', 'post'));
$template->set('menu', Jonah::getMenu('string'));
$template->set('notify', Horde_Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

require JONAH_TEMPLATES . '/common-header.inc';
echo $template->fetch(JONAH_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
