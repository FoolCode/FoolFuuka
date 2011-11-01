<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<div class="list">
	<div class="title">
		<?php echo '<a href="' . site_url('/reader/team/' . $team->stub) . '">' . _('Team\'s page') . ': ' . $team->name . '</a>'; ?>
	</div>

	<?php
	echo '<div class="group">
					<div class="title">' . _('Informations') . '</span></div>
				';
	echo '<div class="element">
					<div class="title">' . _("URL") . ': <a href="' . $team->url . '">' . $team->url . '</a></div></div>
						<div class="element">
					<div class="title">' . _("IRC") . ': <a href="' . parse_irc($team->irc) . '">' . $team->irc . '</a></div></div>
						<div class="element">
					<div class="title">' . _("Twitter") . ': <a href="http://twitter.com/' . $team->twitter . '">http://twitter.com/' . $team->twitter . '</a></div></div>
						<div class="element">
					<div class="title">' . _("Facebook") . ': <a href="' . $team->facebook . '">' . $team->facebook . '</a></div>
				</div></div>';


	echo '<div class="group">
					<div class="title">' . _('Team leaders') . '</div>
				';
	if (count($members) == 0) {
		echo '<div class="element">
					<div class="title">' . _("No leaders in this team") . '.</div>
				</div>';
	}
	else
		foreach ($members->all as $key => $member) {
			if (!$member->is_leader)
				continue;
			echo '<div class="element">
					<div class="image">'.get_gravatar($member->email, 75, NULL, NULL, TRUE).'</div>
					<div class="info"><b>' . (HTMLpurify(($member->profile_display_name)?$member->profile_display_name:$member->username, 'unallowed')) . '</b></div>';
					if($member->profile_bio) echo '<div class="info">'._('Bio').': '.(HTMLpurify($member->profile_bio, 'unallowed')).'</div>';
					if($member->profile_twitter) echo '<div class="info">'._('Twitter').': <a href="http://twitter.com/'.(HTMLpurify($member->profile_twitter, 'unallowed')).'" target="_blank">'.(HTMLpurify($member->profile_twitter, 'unallowed')).'</a></div>';
				echo '</div>';
		}

	echo '</div><div class="group">
					<div class="title">' . _('Members') . '</div>
				';
	if (count($members) == 0) {
		echo '<div class="element">
					<div class="title">' . _("No members in this team") . '.</div>
				</div>';
	}
	else
		foreach ($members->all as $key => $member) {
			if ($member->is_leader)
				continue;
			echo '<div class="element">
					<div class="image">'.get_gravatar($member->email, 75, NULL, NULL, TRUE).'</div>
					<div class="info"><b>' . (HTMLpurify(($member->profile_display_name)?$member->profile_display_name:$member->username, 'unallowed')) . '</b></div>';
					if($member->profile_bio) echo '<div class="info">'._('Bio').': '.(HTMLpurify($member->profile_bio, 'unallowed')).'</div>';
					if($member->profile_twitter) echo '<div class="info">'._('Twitter').': <a href="http://twitter.com/'.(HTMLpurify($member->profile_twitter, 'unallowed')).'" target="_blank">'.(HTMLpurify($member->profile_twitter, 'unallowed')).'</a></div>';
				echo '</div>';
		}
	echo '</div>'
	?>
</div>
