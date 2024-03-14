<?php require_once 'engine/init.php'; include 'layout/overall/header.php'; ?>
<br><table class="blackline">
	<tr>
		<td><img src="layout/images/blank.gif"></td>
	</tr>
</table>
<div class="titleheader">
	<h1>Forum</h1>
</div>
<table class="blackline">
	<tr>
		<td><img src="layout/images/blank.gif"></td>
	</tr>
</table>
<?php
protect_page();
error_reporting(E_ALL ^ E_NOTICE);
if (!$config['forum']['enabled']) admin_only($user_data);
/*  -------------------------------
	---		Znote AAC forum 	---
	-------------------------------
	Created by Znote.
	Version 1.2.

	Changelog (1.0 --> 1.2):
	- Updated to the new date/clock time system
	- Bootstrap design support.
*/
// BBCODE support:
function TransformToBBCode($string) {
	$tags = array(
		'[center]{$1}[/center]' => '<center>$1</center>',
		'[b]{$1}[/b]' => '<b>$1</b>',
		'[img]{$1}[/img]'    => '<a href="$1" target="_BLANK"><img src="$1" alt="image" style="width: 100%"></a>',
		'[link]{$1}[/link]'    => '<a href="$1">$1</a>',
		'[link={$1}]{$2}[/link]'   => '<a href="$1" target="_BLANK">$2</a>',
		'[color={$1}]{$2}[/color]' => '<font color="$1">$2</font>',
		'[*]{$1}[/*]' => '<li>$1</li>',
		'[youtube]{$1}[/youtube]' => '<div class="youtube"><div class="aspectratio"><iframe src="//www.youtube.com/embed/$1" frameborder="0" allowfullscreen></iframe></div></div>',
	);

	foreach ($tags as $tag => $value) {
		$code = preg_replace('/placeholder([0-9]+)/', '(.*?)', preg_quote(preg_replace('/\{\$([0-9]+)\}/', 'placeholder$1', $tag), '/'));
		$string = preg_replace('/'.$code.'/i', $value, $string);
	}

	return $string;
}
Function PlayerHaveAccess($yourChars, $playerName){
	$access = false;
	foreach($yourChars as $char) {
		if ($char['name'] == $playerName) $access = true;
	}
	return $access;
}

// forum layout by peonso functions
		function border_class($bc) {
			if ($bc === 1) {
				echo 'class="lightborder"';
			} else {
				echo 'class="darkborder"';
			}
		}
?><style>tr.darkborder  td{ background: #101018; !important; }</style><?php
		
// bootstrap alert for errors
$alert = '<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>';
$alert_green = '<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>';

// END of forum layout by peonso functons

// Start page init
$admin = is_admin($user_data);
if ($admin) $yourChars = mysql_select_multi("SELECT `id`, `name`, `group_id` FROM `players` WHERE `level`>='1' AND `account_id`='". $user_data['id'] ."';");
else $yourChars = mysql_select_multi("SELECT `id`, `name`, `group_id` FROM `players` WHERE `level`>='". $config['forum']['level'] ."' AND `account_id`='". $user_data['id'] ."';");
if (!$yourChars) $yourChars = array();
$charCount = count($yourChars);
$yourAccess = accountAccess($user_data['id'], $config['TFSVersion']);
if ($admin) {
	if (!empty($_POST)) {
		$guilds = mysql_select_multi("SELECT `id`, `name` FROM `guilds` ORDER BY `name`;");
		$guilds[] = array('id' => '0', 'name' => 'No guild');
	}
	$yourAccess = 100;
}

// Your characters, indexed by char_id
$charData = array();
foreach ($yourChars as $char) {
	$charData[$char['id']] = $char;
	if (get_character_guild_rank($char['id']) > 0) {
		$guild = get_player_guild_data($char['id']);
		$charData[$char['id']]['guild'] = $guild['guild_id'];
		$charData[$char['id']]['guild_rank'] = $guild['rank_level'];
	} else $charData[$char['id']]['guild'] = '0';
}
$cooldownw = array(
	$user_znote_data['cooldown'],
	time(),
	$user_znote_data['cooldown'] - time()
	);

/////////////////
// Guild Leader & admin
$leader = false;
foreach($charData as $char) {
	if ($char['guild'] > 0 && $char['guild_rank'] == 3) $leader = true;
}
if ($admin && !empty($_POST) || $leader && !empty($_POST)) {
	$admin_thread_delete = getValue($_POST['admin_thread_delete']);
	$admin_thread_close = getValue($_POST['admin_thread_close']);
	$admin_thread_open = getValue($_POST['admin_thread_open']);
	$admin_thread_sticky = getValue($_POST['admin_thread_sticky']);
	$admin_thread_unstick = getValue($_POST['admin_thread_unstick']);
	$admin_thread_id = getValue($_POST['admin_thread_id']);

	// delete thread
	if ($admin_thread_delete !== false) {
		$admin_thread_id = (int)$admin_thread_id;
		$access = false;
		if (!$admin) {
			$thread = mysql_select_single("SELECT `forum_id` FROM `znote_forum_threads` WHERE `id`='$admin_thread_id';");
			$forum = mysql_select_single("SELECT `guild_id` FROM `znote_forum` WHERE `id`='". $thread['forum_id'] ."';");
			foreach($charData as $char) if ($char['guild'] == $forum['guild_id'] && $char['guild_rank'] == 3) $access = true;
		} else $access = true;

		if ($access) {
			// Delete all associated posts
			mysql_delete("DELETE FROM `znote_forum_posts` WHERE `thread_id`='$admin_thread_id';");
			// Delete thread itself
			mysql_delete("DELETE FROM `znote_forum_threads` WHERE `id`='$admin_thread_id' LIMIT 1;");
			echo $alert_green.'Thread and all associated posts deleted.</div>';
		} else echo $alert.'Permission denied.</div>';
	}

	// Close thread
	if ($admin_thread_close !== false) {
		$admin_thread_id = (int)$admin_thread_id;
		$access = false;
		if (!$admin) {
			$thread = mysql_select_single("SELECT `forum_id` FROM `znote_forum_threads` WHERE `id`='$admin_thread_id';");
			$forum = mysql_select_single("SELECT `guild_id` FROM `znote_forum` WHERE `id`='". $thread['forum_id'] ."';");
			foreach($charData as $char) if ($char['guild'] == $forum['guild_id'] && $char['guild_rank'] == 3) $access = true;
		} else $access = true;
		if ($access) {
			mysql_update("UPDATE `znote_forum_threads` SET `closed`='1' WHERE `id`='$admin_thread_id' LIMIT 1;");
			//die("UPDATE `znote_forum_threads` SET `closed`='1' WHERE `id`='$admin_thread_id' LIMIT 1;");
			echo $alert_green.'Thread has been closed.</div>';
		} else echo $alert.'Permission denied.</div>';
	}

	// open thread
	if ($admin_thread_open !== false) {
		$admin_thread_id = (int)$admin_thread_id;
		$access = false;
		if (!$admin) {
			$thread = mysql_select_single("SELECT `forum_id` FROM `znote_forum_threads` WHERE `id`='$admin_thread_id';");
			$forum = mysql_select_single("SELECT `guild_id` FROM `znote_forum` WHERE `id`='". $thread['forum_id'] ."';");
			foreach($charData as $char) if ($char['guild'] == $forum['guild_id'] && $char['guild_rank'] == 3) $access = true;
		} else $access = true;
		if ($access) {
			mysql_update("UPDATE `znote_forum_threads` SET `closed`='0' WHERE `id`='$admin_thread_id' LIMIT 1;");
			echo $alert_green.'Thread has been opened.</div>';
		} else echo $alert.'Permission denied.</div>';
	}

	// stick thread
	if ($admin_thread_sticky !== false) {
		$admin_thread_id = (int)$admin_thread_id;
		$access = false;
		if (!$admin) {
			$thread = mysql_select_single("SELECT `forum_id` FROM `znote_forum_threads` WHERE `id`='$admin_thread_id';");
			$forum = mysql_select_single("SELECT `guild_id` FROM `znote_forum` WHERE `id`='". $thread['forum_id'] ."';");
			foreach($charData as $char) if ($char['guild'] == $forum['guild_id'] && $char['guild_rank'] == 3) $access = true;
		} else $access = true;
		if ($access) {
			mysql_update("UPDATE `znote_forum_threads` SET `sticky`='1' WHERE `id`='$admin_thread_id' LIMIT 1;");
			echo $alert_green.'Thread has been sticked.</div>';
		} else echo $alert.'Permission denied.</div>';
	}

	// unstick thread
	if ($admin_thread_unstick !== false) {
		$admin_thread_id = (int)$admin_thread_id;
		$access = false;
		if (!$admin) {
			$thread = mysql_select_single("SELECT `forum_id` FROM `znote_forum_threads` WHERE `id`='$admin_thread_id';");
			$forum = mysql_select_single("SELECT `guild_id` FROM `znote_forum` WHERE `id`='". $thread['forum_id'] ."';");
			foreach($charData as $char) if ($char['guild'] == $forum['guild_id'] && $char['guild_rank'] == 3) $access = true;
		} else $access = true;
		if ($access) {
			mysql_update("UPDATE `znote_forum_threads` SET `sticky`='0' WHERE `id`='$admin_thread_id' LIMIT 1;");
			echo $alert_green.'Thread has been unsticked.</div>';
		} else echo $alert.'Permission denied.</div>';
	}
}

/////////////////
// ADMIN FUNCT
if ($admin && !empty($_POST)) {
	$admin_post_id = getValue($_POST['admin_post_id']);
	$admin_post_delete = getValue($_POST['admin_post_delete']);

	$admin_category_delete = getValue($_POST['admin_category_delete']);
	$admin_category_edit = getValue($_POST['admin_category_edit']);
	$admin_category_id = getValue($_POST['admin_category_id']);

	$admin_update_category = getValue($_POST['admin_update_category']);
	$admin_category_name = getValue($_POST['admin_category_name']);
	$admin_category_access = getValue($_POST['admin_category_access']);
	$admin_category_closed = getValue($_POST['admin_category_closed']);
	$admin_category_hidden = getValue($_POST['admin_category_hidden']);
	$admin_category_guild_id = getValue($_POST['admin_category_guild_id']);

	$admin_board_create_name = getValue($_POST['admin_board_create_name']);
	$admin_board_create_access = getValue($_POST['admin_board_create_access']);
	$admin_board_create_closed = getValue($_POST['admin_board_create_closed']);
	$admin_board_create_hidden = getValue($_POST['admin_board_create_hidden']);
	$admin_board_create_guild_id = getValue($_POST['admin_board_create_guild_id']);
	
	// Create board
	if ($admin_board_create_name !== false) {
		
		// Insert data
		mysql_insert("INSERT INTO `znote_forum` (`name`, `access`, `closed`, `hidden`, `guild_id`) 
			VALUES ('$admin_board_create_name', 
				'$admin_board_create_access', 
				'$admin_board_create_closed', 
				'$admin_board_create_hidden', 
				'$admin_board_create_guild_id');");
		echo $alert_green.'Board has been created.</div>';
	}

	//////////////////
	// update category
	if ($admin_update_category !== false) {
		$admin_category_id = (int)$admin_category_id;

		// Update the category
		mysql_update("UPDATE `znote_forum` SET 
			`name`='$admin_category_name', 
			`access`='$admin_category_access', 
			`closed`='$admin_category_closed', 
			`hidden`='$admin_category_hidden', 
			`guild_id`='$admin_category_guild_id' 
			WHERE `id`='$admin_category_id' LIMIT 1;");
		echo $alert_green.'Board has been updated successfully.</div>';
	}

	//////////////////
	// edit category
	if ($admin_category_edit !== false) {
		$admin_category_id = (int)$admin_category_id;
		$category = mysql_select_single("SELECT `id`, `name`, `access`, `closed`, `hidden`, `guild_id` 
			FROM `znote_forum` WHERE `id`='$admin_category_id' LIMIT 1;");
		if ($category !== false) {
			?>
			<form action="" method="post">
				<input type="hidden" name="admin_category_id" value="<?php echo $category['id']; ?>">
				<table class="updateTable table table-striped">
					<tr><td colspan="2">
						Edit Board</td>
					</tr>
					<tr class="darkborder">
						<td><label for="admin_category_name">Board name:</label></td>
						<td><input name="admin_category_name" value="<?php echo $category['name']; ?>" class="span12"></td>

					</tr>
					<tr class="darkborder">
						<td><label for="admin_category_access">Required Access:</label></td>
						<td>
							<select name="admin_category_access" class="span12">
								<?php
								foreach($config['ingame_positions'] as $access => $name) {
									if ($access == $category['access']) echo "<option value='$access' selected>$name</option>";
									else echo "<option value='$access'>$name</option>";
								}
								?>
							</select>
						</td>
					</tr>
					<tr class="darkborder">
						<td><label for="admin_category_closed">Closed:</label></td>
						<td>
							<select name="admin_category_closed" class="span12">
								<?php 
								if ($category['closed'] == 1) echo '<option value="1" selected>Yes</option>';
								else echo '<option value="1">Yes</option>';
								if ($category['closed'] == 0) echo '<option value="0" selected>No</option>';
								else echo '<option value="0">No</option>';
								?>
							</select>
						</td>
					</tr>
					<tr class="darkborder">
						<td><label for="admin_category_hidden">Hidden:</label></td>
						<td>
							<select name="admin_category_hidden" class="span12">
								<?php 
								if ($category['hidden'] == 1) echo '<option value="1" selected>Yes</option>';
								else echo '<option value="1">Yes</option>';
								if ($category['hidden'] == 0) echo '<option value="0" selected>No</option>';
								else echo '<option value="0">No</option>';
								?>
							</select>
						</td>
					</tr>
					<tr class="darkborder">
						<td><label for="admin_category_guild_id">Guild id:</label></td>
						<td>
							<select name="admin_category_guild_id" class="span12">
								<?php foreach($guilds as $guild) {
									if ($category['guild_id'] == $guild['id']) echo "<option value='". $guild['id'] ."' selected>". $guild['name'] ."</option>";
									else echo "<option value='". $guild['id'] ."'>". $guild['name'] ."</option>";
								} ?>
							</select>
						</td>
					</tr>
					<tr class="darkborder">
						<td colspan="2"><input type="submit" name="admin_update_category" value="Update Board" class="btn btn-success"></td>
					</tr>
				</table>
			</form>
			<br>
			<?php
		} else echo $alert.'Category not found.</div>';
		
	}

	// delete category
	if ($admin_category_delete !== false) {
		$admin_category_id = (int)$admin_category_id;

		// find all threads in category
		$threads = mysql_select_multi("SELECT `id` FROM `znote_forum_threads` WHERE `forum_id`='$admin_category_id';");

		// Then loop through all threads, and delete all associated posts:
		foreach($threads as $thread) {
			mysql_delete("DELETE FROM `znote_forum_posts` WHERE `thread_id`='". $thread['id'] ."';");
		}
		// Then delete all threads
		mysql_delete("DELETE FROM `znote_forum_threads` WHERE `forum_id`='$admin_category_id';");
		// Then delete the category
		mysql_delete("DELETE FROM `znote_forum` WHERE `id`='$admin_category_id' LIMIT 1;");
		echo $alert_green.'Board, associated threads and all their associated posts deleted.</div>';
	}

	// delete post
	if ($admin_post_delete !== false) {
		$admin_post_id = (int)$admin_post_id;

		// Delete the post
		mysql_delete("DELETE FROM `znote_forum_posts` WHERE `id`='$admin_post_id' LIMIT 1;");
		echo $alert_green.'Post has been deleted.</div>';
	}
}
// End admin function

// Fetching get values
if (!empty($_GET)) {
	$getCat = getValue($_GET['cat']);
	$getForum = getValue($_GET['forum']);
	$getThread = getValue($_GET['thread']);

	$new_thread_category = getValue($_POST['new_thread_category']);
	$new_thread_cid = getValue($_POST['new_thread_cid']);

	$create_thread_cid = getValue($_POST['create_thread_cid']);
	$create_thread_title = getValue($_POST['create_thread_title']);
	$create_thread_text = getValue($_POST['create_thread_text']);
	$create_thread_category = getValue($_POST['create_thread_category']);

	$update_thread_id = getValue($_POST['update_thread_id']);
	$update_thread_title = getValue($_POST['update_thread_title']);
	$update_thread_text = getValue($_POST['update_thread_text']);

	$edit_thread = getValue($_POST['edit_thread']);
	$edit_thread_id = getValue($_POST['edit_thread_id']);

	$reply_thread = getValue($_POST['reply_thread']);
	$reply_text = getValue($_POST['reply_text']);
	$reply_cid = getValue($_POST['reply_cid']);

	$edit_post = getValue($_POST['edit_post']);
	$edit_post_id = getValue($_POST['edit_post_id']);

	$update_post_id = getValue($_POST['update_post_id']);
	$update_post_text = getValue($_POST['update_post_text']);

	/////////////////////
	// When you are POSTING in an existing thread
	if ($reply_thread !== false && $reply_text !== false && $reply_cid !== false) {
		$reply_cid = (int)$reply_cid;

		if ($user_znote_data['cooldown'] < time()) {
			user_update_znote_account(array('cooldown'=>(time() + $config['forum']['cooldownPost'])));

			$thread = mysql_select_single("SELECT `closed` FROM `znote_forum_threads` WHERE `id`='$reply_thread' LIMIT 1;");

			if ($thread['closed'] == 1 && $admin === false) $access = false;
			else $access = true;

			if ($access) {
				mysql_insert("INSERT INTO `znote_forum_posts` (`thread_id`, `player_id`, `player_name`, `text`, `created`, `updated`) VALUES ('$reply_thread', '$reply_cid', '". $charData[$reply_cid]['name'] ."', '$reply_text', '". time() ."', '". time() ."');");
				mysql_update("UPDATE `znote_forum_threads` SET `updated`='". time() ."' WHERE `id`='$reply_thread';");
			} else echo $alert.'You don\'t have permission to post on this thread. [Thread: Closed]</div>';
		} else {
			echo $alert.'Antispam: You need to wait '.$user_znote_data['cooldown'] - time().' seconds before you can create or post.</div>';
		}
	}

	/////////////////////
	// When you ARE creating new thread
	if ($create_thread_cid !== false && $create_thread_title !== false && $create_thread_text !== false && $create_thread_category !== false) {
		if ($user_znote_data['cooldown'] < time()) {
			user_update_znote_account(array('cooldown'=>(time() + $config['forum']['cooldownCreate'])));

			$category = mysql_select_single("SELECT `access`, `closed`, `guild_id` FROM `znote_forum` WHERE `id`='$create_thread_category' LIMIT 1;");
			if ($category !== false) {
				$access = true;
				if (!$admin) {
					if ($category['access'] > $yourAccess) $access = false;
					if ($category['guild_id'] > 0) {
						$status = false;
						foreach($charData as $char) {
							if ($char['guild'] == $category['guild_id']) $status = true;
						}
						if (!$status) $access = false;
					}
					if ($category['closed'] > 0) $access = false;
				}

				if ($access) {
					mysql_insert("INSERT INTO `znote_forum_threads`	
						(`forum_id`, `player_id`, `player_name`, `title`, `text`, `created`, `updated`, `sticky`, `hidden`, `closed`) 
						VALUES (
							'$create_thread_category', 
							'$create_thread_cid', 
							'". $charData[$create_thread_cid]['name'] ."', 
							'$create_thread_title', 
							'$create_thread_text', 
							'". time() ."', 
							'". time() ."', 
							'0', '0', '0');");
					SendGet(array('cat'=>$create_thread_category), 'forum.php');
				} else echo $alert.'Permission to create thread denied.</div>';
			} else echo $alert.'Category does not exist.</div>';
		} else {
			echo $alert.'Antispam: You need to wait '.$user_znote_data['cooldown'] - time().' seconds before you can create or post.</div>';
		}
	}

	/////////////////////
	// When you ARE updating post
	if ($update_post_id !== false && $update_post_text !== false) {
		// Fetch the post data
		$post = mysql_select_single("SELECT `id`, `player_name`, `text`, `thread_id` FROM `znote_forum_posts` WHERE `id`='$update_post_id' LIMIT 1;");
		$thread = mysql_select_single("SELECT `closed` FROM `znote_forum_threads` WHERE `id`='". $post['thread_id'] ."' LIMIT 1;");

		// Verify access
		$access = PlayerHaveAccess($yourChars, $post['player_name']);
		if ($thread !== false && $thread['closed'] == 1 && $admin === false) $access = false;
		if ($admin) $access = true;
		//if ($thread === false) $access = false;

		if ($access) {
			mysql_update("UPDATE `znote_forum_posts` SET `text`='$update_post_text', `updated`='". time() ."' WHERE `id`='$update_post_id';");
			echo $alert_green.'post has been updated.</div>';
		} else echo $alert.'Your permission to edit this post has been denied.</div>';
	}

	/////////////////////
	// When you ARE updating thread
	if ($update_thread_id !== false && $update_thread_title !== false && $update_thread_text !== false) {
		// Fetch the thread data
		$thread = mysql_select_single("SELECT `id`, `player_name`, `title`, `text`, `closed` FROM `znote_forum_threads` WHERE `id`='$update_thread_id' LIMIT 1;");

		// Verify access
		$access = PlayerHaveAccess($yourChars, $thread['player_name']);
		if ($thread['closed'] == 1 && $admin === false) $access = false;
		if ($admin) $access = true;

		if ($access) {
			mysql_update("UPDATE `znote_forum_threads` SET `title`='$update_thread_title', `text`='$update_thread_text' WHERE `id`='$update_thread_id';");
			echo $alert_green.'Thread has been updated.</div>';
		} else echo $alert.'Your permission to edit this thread has been denied.</div>';
	}

	/////////////////////
	// When you want to edit a post
	if ($edit_post_id !== false && $edit_post !== false) {
		// Fetch the post data
		$post = mysql_select_single("SELECT `id`, `thread_id`, `text`, `player_name` FROM `znote_forum_posts` WHERE `id`='$edit_post_id' LIMIT 1;");
		$thread = mysql_select_single("SELECT `closed` FROM `znote_forum_threads` WHERE `id`='". $post['thread_id'] ."' LIMIT 1;");
		// Verify access
		$access = PlayerHaveAccess($yourChars, $post['player_name']);
		if ($thread['closed'] == 1 && $admin === false) $access = false;
		if ($admin) $access = true;

		if ($access) {
			?>
			<h1>Edit Post</h1>
			<table class="table">
			<tr><td>Edit Post</td></tr>
			<tr><td>
			<form type="" method="post">
				<input name="update_post_id" type="hidden" value="<?php echo $post['id']; ?>">
				<textarea name="update_post_text" style="width: 610px; height: 300px"><?php echo $post['text']; ?></textarea><br><br>
				<input type="submit" value="Update Post" class="btn btn-success">
			</form>
			</td></tr>
			</table>
			<?php
		} else echo $alert.'You don\'t have permission to edit this post.</div>';
	} else

	/////////////////////
	// When you want to edit a thread
	if ($edit_thread_id !== false && $edit_thread !== false) {
		// Fetch the thread data
		$thread = mysql_select_single("SELECT `id`, `title`, `text`, `player_name`, `closed` FROM `znote_forum_threads` WHERE `id`='$edit_thread_id' LIMIT 1;");

		$access = PlayerHaveAccess($yourChars, $thread['player_name']);
		if ($thread['closed'] == 1) $access = false;
		if ($admin) $access = true;

		if ($access) {
			?>
			<table class="table">
			<tr><th>Edit Thread</th></tr>
			<tr><td>
			<form type="" method="post"><br>
				<input name="update_thread_id" type="hidden" value="<?php echo $thread['id']; ?>">
				<input name="update_thread_title" type="text" value="<?php echo $thread['title']; ?>" class="form-control"><br>
				<textarea name="update_thread_text" style="height: 300px" class="form-control"><?php echo $thread['text']; ?></textarea><br>
				<input type="submit" value="Update Thread" class="btn btn-success">
			</form>
			</td></tr>
			</table>
			<?php
		} else echo $alert.'Edit access denied.</div>';
	} else

	/////////////////////
	// When you want to view a thread
	if ($getThread !== false) {
		$getThread = (int)$getThread;
		$threadData = mysql_select_single("SELECT `t`.`id` AS `id`, `t`.`forum_id` AS `forum_id`, `t`.`player_id` AS `player_id`, `t`.`player_name` AS `player_name`, `t`.`title` AS `title`, `t`.`text` AS `text`, `t`.`created` AS `created`, `t`.`updated` AS `updated`, `t`.`sticky` AS `sticky`, `t`.`hidden` AS `hidden`, `t`.`closed` AS `closed`, `f`.`name` AS `board` FROM `znote_forum_threads` AS `t` JOIN `znote_forum` AS `f` ON `t`.`forum_id` = `f`.`id` WHERE `t`.`id`='$getThread' LIMIT 1;");

		if ($threadData !== false) {

			$category = mysql_select_single("SELECT `hidden`, `access`, `guild_id` FROM `znote_forum` WHERE `id`='". $threadData['forum_id'] ."' LIMIT 1;");
			if ($category === false) die("Thread category does not exist.");

			$access = true;
			$leader = false;
			if ($category['hidden'] == 1 || $category['access'] > 1 || $category['guild_id'] > 0) {
				$access = false;
				if ($category['hidden'] == 1) $access = PlayerHaveAccess($yourChars, $threadData['player_name']);
				if ($category['access'] > 1 && $yourAccess >= $category['access']) $access = true;
				foreach($charData as $char) {
					if ($category['guild_id'] == $char['guild']) $access = true;
					if ($char['guild_rank'] == 3) $leader = true;
				}
				if ($admin) $access = true;
			}


			if ($access) {
				$bordercolor = 1;
				?>
				<ol class="breadcrumb">
					<li><strong><a href='forum.php'>Forum</a></strong></li>
					<li><strong><a href="?cat=<?php echo $getCat; ?>"><?php echo $threadData['board'];?></a></strong></li>
					<li><strong><?php echo $threadData['title'];?></strong></li>
				</ol>
				<h3><strong><?php echo $threadData['title']; ?></h3>
				<!--<span style="font-size:85%;opacity:.5;">Thread in '<?php echo $getForum; ?>' started by <?php echo $threadData['player_name'];?>, <?php echo date("j M Y", $threadData['created']) ?>.</span><br>-->

				<table class="znoteTable ThreadTable table">
					<tr>
						<th width="23%"><span style="font-size:85%;">Author</span></th>
						<th></th>
					</tr>
				
					<tr <?php border_class($bordercolor);?>>
						<td valign="top"><?php echo "<a href='characterprofile.php?name=". $threadData['player_name'] ."'>". $threadData['player_name'] ."</a>";?><br>
						<span style="font-size:85%"><?php 
						$profile_name = sanitize($threadData['player_name']);
						$profile_data = mysql_select_single("SELECT `name`, `group_id`, `vocation`, `level` FROM `players` WHERE `name` = '$profile_name' ;");  
						
						if ($profile_data['group_id'] > 1) {
							foreach ($config['ingame_positions'] as $key=>$value) {
								if ($key == $profile_data['group_id']+2) {
									echo $value;
									echo '<br>';
								}
							} 
						}?>
						<br>
						Vocation: <?php echo vocation_id_to_name($profile_data['vocation']); ?><br>
						Level: <?php echo $profile_data['level']; ?><br><br></span>
						</td>
						<td valign="top">
							<p><?php echo nl2br(TransformToBBCode($threadData['text'])); ?></p>
						</td>
					</tr>
					<tr <?php border_class($bordercolor);?> height="30px">
						<td valign="top">
							<span style="font-size:75%;"><?php echo getClock($threadData['created'], true); ?></span>
						</td>
						<td align="right">
				<?php
				if ($admin || $leader) {
					// PlayerHaveAccess($yourChars, $thread['player_name']) || 
					// $yourChars
					?>
									<form action="" method="post" style="display:inline; padding-right: 4px;">
										<input type="hidden" name="admin_thread_id" value="<?php echo $threadData['id']; ?>">
										<input type="submit" name="admin_thread_delete" value="Delete Thread" class="btn btn-danger btn-xs">
									</form>
								<?php if ($threadData['closed'] == 0) { ?>
									<form action="" method="post" style="display:inline; padding-right: 4px;">
										<input type="hidden" name="admin_thread_id" value="<?php echo $threadData['id']; ?>">
										<input type="submit" name="admin_thread_close" value="Close Thread" class="btn btn-warning btn-xs">
									</form>
								<?php } else { ?>
									<form action="" method="post" style="display:inline; padding-right: 4px;">
										<input type="hidden" name="admin_thread_id" value="<?php echo $threadData['id']; ?>">
										<input type="submit" name="admin_thread_open" value="Open Thread" class="btn btn-success btn-xs">
									</form>
								<?php } ?>
								<?php if ($threadData['sticky'] == 0) { ?>
									<form action="" method="post" style="display:inline; padding-right: 4px;">
										<input type="hidden" name="admin_thread_id" value="<?php echo $threadData['id']; ?>">
										<input type="submit" name="admin_thread_sticky" value="Stick thread" class="btn btn-info btn-xs">
									</form>
								<?php } else { ?>
									<form action="" method="post" style="display:inline; padding-right: 4px;">
										<input type="hidden" name="admin_thread_id" value="<?php echo $threadData['id']; ?>">
										<input type="submit" name="admin_thread_unstick" value="Unstick thread" class="btn btn-primary btn-xs">
									</form>
								<?php } ?>
									<form action="" method="post" style="display:inline; padding-right: 4px;">
										<input type="hidden" name="edit_thread_id" value="<?php echo $threadData['id']; ?>">
										<input type="submit" name="edit_thread" value="Edit Thread" class="btn btn-success btn-xs">
									</form>
					<?php
				} else {
					if ($threadData['closed'] == 0 && PlayerHaveAccess($yourChars, $threadData['player_name'])) {
						?>
									<form action="" method="post" style="display:inline; padding-right: 4px;">
										<input type="hidden" name="edit_thread_id" value="<?php echo $threadData['id']; ?>">
										<input type="submit" name="edit_thread" value="Edit Thread" class="btn btn-success btn-xs">
									</form>
						<?php
					}
				}
				?>
						</td>
					</tr>				
				<?php
				// Display replies... (copy table above and edit each post)
				$posts = mysql_select_multi("SELECT `id`, `player_name`, `text`, `created`, `updated` FROM `znote_forum_posts` WHERE `thread_id`='". $threadData['id'] ."' ORDER BY `created`;");
				if ($posts !== false) {
					foreach($posts as $post) {
						if ($bordercolor === 1) {
						$bordercolor = 0;
						} else {
						$bordercolor = 1;
						}
						?>
							<tr <?php border_class($bordercolor);?>>
								<td valign="top"><?php echo "<a href='characterprofile.php?name=". $post['player_name'] ."'>". $post['player_name'] ."</a>";?><br>
						<span style="font-size:85%"><?php 
						$post_name = sanitize($post['player_name']);
						$post_data = mysql_select_single("SELECT `name`, `group_id`, `vocation`, `level` FROM `players` WHERE `name` = '$post_name' ;");  

						if ($post_data['group_id'] > 1) {
							foreach ($config['ingame_positions'] as $key=>$value) {
								if ($key == $post_data['group_id']+2) {
									echo $value;
									echo '<br>';
								}
							} 
						}?>
						<br>
						Vocation: <?php echo vocation_id_to_name($post_data['vocation']); ?><br>
						Level: <?php echo $post_data['level']; ?><br><br></span>
						</td>

								<td valign="top">
									<p><?php echo nl2br(TransformToBBCode($post['text'])); ?></p>
								</td>
							</tr>
							<tr <?php border_class($bordercolor);?> height="30px">
								<td valign="top">
									<span style="font-size:75%;"><?php echo getClock($post['created'], true); ?></span>
								</td>
								<td  align="right">


						<?php
						if (PlayerHaveAccess($yourChars, $post['player_name']) || $admin) {
							?><?php
							if ($admin) {
								?>
								<form action="" method="post" class="postButton" style="display:inline; padding-right: 4px;">
									<input type="hidden" name="admin_post_id" value="<?php echo $post['id']; ?>">
									<input type="submit" name="admin_post_delete" value="Delete Post" class="btn btn-danger btn-xs">
								</form>
								<?php
							}
							if ($threadData['closed'] == 0 || $admin) {
								?>
								<form action="" method="post" class="postButton" style="display:inline; padding-right: 4px;">
									<input type="hidden" name="edit_post_id" value="<?php echo $post['id']; ?>">
									<input type="submit" name="edit_post" value="Edit Post" class="btn btn-success btn-xs">
								</form>
								<?php
							}
							?><?php
						}
						?></td></tr><?php
					}
				}
				?></table><br><?php

				// Quick Reply
				if ($charCount > 0) {
					if ($threadData['closed'] == 0 || $yourAccess > 3) {
						?>
						<table class="table">
						<tr><th>Quick Reply</th></tr>
						<tr><td>
						<form action="" method="post">
							<input name="reply_thread" type="hidden" value="<?php echo $threadData['id']; ?>">
							<abbr title="[b]Bold Text[/b], [img]Direct Image Link[/img], [center]Cented Text[/center], [link]http://youtube.com/ [/link], [color=GREEN]Green Text![/color], [*] - Dotted [/*], [youtube]dQw4w9WgXcQ[/youtube]"><a>BB Code info</a></abbr>
							<br>
							<br>
							<textarea class="forumReply" name="reply_text" style="width: 610px; height: 150px" class="form-control"></textarea><br><br>
							<div class="col-lg-3"><select name="reply_cid" class="form-control">
								<?php
								foreach($yourChars as $char) {
									echo "<option value='". $char['id'] ."'>". $char['name'] ."</option>";
								}
								?>
							</select>
							</div><div class="col-lg-3">
							<input name="" type="submit" value="Post Reply" class="btn btn-primary">
							</div>
						</form>
						</td></tr>
						</table>
						<?php
					} else echo $alert.'You don\'t have permission to post on this thread. [Thread: Closed]</div>';
				} else {
					?><p>You must have a character on your account that is level <?php echo $config['forum']['level']; ?>+ to reply to this thread.</p><?php
				}
			} else echo $alert.'Your permission to access this thread has been denied.</div>';
		} else {
			?>
			<p>Thread is unavailable for you, or do not exist any more.<br>
				<?php
				if ($_GET['cat'] > 0 && !empty($_GET['forum'])) {
					$tmpCat = getValue($_GET['cat']);
					$tmpCatName = getValue($_GET['forum']);
					?>
					<br>Go back to: <a href="forum.php?forum=<?php echo $tmpCatName; ?>&cat=<?php echo $tmpCat; ?>"><?php echo $tmpCatName; ?></a></p>
					<?php
				} else {
					?>
					<br><a href="forum.php">Go back to Forum</a></p>
					<?php
				}
				?>
			<?php
		}
		
	} else

	/////////////////////
	// When you want to create a new thread
	if ($new_thread_category !== false && $new_thread_cid !== false) {
		// Verify we got access to this category
		$category = mysql_select_single("SELECT `access`, `closed`, `guild_id` FROM `znote_forum` WHERE `id`='$new_thread_category' LIMIT 1;");
		if ($category !== false) {
			$access = true;
			if (!$admin) {
				if ($category['access'] > $yourAccess) $access = false;
				if ($category['guild_id'] > 0) {
					$status = false;
					foreach($charData as $char) {
						if ($char['guild'] == $category['guild_id']) $status = true;
					}
					if (!$status) $access = false;
				}
				if ($category['closed'] > 0) $access = false;
			}

			if ($access) {
				?><table class="table"><tr><th>Create new thread</th></tr><tr><td>
				<form type="" method="post">
					<div class="col-lg-2" style="padding-top:16px; padding-bottom:10px;"><input type="text" disabled value="<?php echo $charData[$new_thread_cid]['name']; ?>" style="width: 100px;" class="form-control"></div>
					<div class="col-lg-10" style="padding-top:16px; padding-bottom:10px;">	<input name="create_thread_cid" type="hidden" value="<?php echo $new_thread_cid; ?>" class="form-control">
					<input name="create_thread_category" type="hidden" value="<?php echo $new_thread_category; ?>" class="form-control">
					<input name="create_thread_title" type="text" placeholder="Thread Subject" class="form-control"></div><div class="col-lg-12" style="padding-bottom:10px;">
					<textarea name="create_thread_text" style="width: 100%; height: 250px" placeholder="Message" class="form-control"></textarea></div>
					<div class="col-lg-12"><input type="submit" value="Create Thread" class="btn btn-success"></div>
				</form>
				</td></tr></table>
				<?php
			} else echo $alert.'Permission to create thread denied.</div>';
		}
	} else

	/////////////////////
	// When category is specified
	if ($getCat !== false) {
		$getCat = (int)$getCat;

		// Fetch category rules
		$category = mysql_select_single("SELECT `name`, `access`, `closed`, `hidden`, `guild_id` FROM `znote_forum` WHERE `id`='$getCat' AND `access`<='$yourAccess' LIMIT 1;");

		if ($category !== false && $category['guild_id'] > 0 && !$admin) {
			$access = false;
			foreach($charData as $char) if ($category['guild_id'] == $char['guild']) $access = true;
			if ($access !== true) $category = false;
		}

		if ($category !== false) {
			// TODO : Verify guild access
			//foreach($charData)
			echo '<ol class="breadcrumb">
					<li><strong><a href=\'forum.php\'>Forum</a></strong></li>
					<li><strong>'. $category['name'] .'</strong></li>
				</ol>';
			// Threads
			//  - id - forum_id - player_id - player_name - title - text - created - updated - sticky - hidden - closed
			$threads = mysql_select_multi("SELECT `id`, `player_name`, `title`, `sticky`, `closed`, `updated` FROM `znote_forum_threads` WHERE `forum_id`='$getCat' ORDER BY `sticky` DESC, `updated` DESC;");

			///// HTML \\\\\
			if ($threads !== false) {
				?>
				<table class="znoteTable table table-striped table-hover" id="forumThreadTable">
					<tr>
						<th width="35%">Title</th>
						<th width="20%">Author</th>
						<th width="12%">Replies</th>
						<th>Last Post</th>
					</tr>
					<?php
					foreach($threads as $thread) {
						$access = true;
						if ($category['hidden'] == 1) {
							if (!$admin) $access = false;
							$access = PlayerHaveAccess($yourChars, $thread['player_name']);
							if ($yourAccess > 3) $access = true;
						}

						if ($access) {
							?>
							<tr>
								<td>
								<?php
									if ($thread['sticky'] == 1) echo $config['forum']['sticky'],' ';
									if ($thread['closed'] == 1) echo $config['forum']['closed'],' ';
									$url = url("forum.php?forum=". $category['name'] ."&cat=". $getCat ."&thread=". $thread['id']);
								?>
								<a href="<?php echo $url;?>"><?php echo $thread['title'];?></a>
								</td>
								<td>
								<?php
									$url = url("characterprofile.php?name=". $thread['player_name']);
								?>
								<a href="<?php echo $url;?>"><?php echo $thread['player_name'];?></a>
								</td>
								<td>
								<?php
									$threadid = $thread['id'];
									$posts = mysql_select_multi("SELECT `player_name`, `updated` FROM `znote_forum_posts` WHERE `thread_id`=$threadid ORDER BY `updated` DESC");
									if (!empty($posts)) { 
										$replies = count($posts);
										foreach($posts as $post) {
											$lastposter = $post['player_name'];
											$lastpostdate = $post['updated'];
											break; 
										}
									} else {
										$replies = 0;
										$lastposter = $thread['player_name'];
										$lastpostdate = $thread['updated'];
									}
									echo $replies;
								?>
								</td>
								<td>
								<?php
								$url = url("characterprofile.php?name=". $lastposter);
								?>
								<span style="font-size:75%;"><?php echo getClock($lastpostdate, true); ?><br>
								by <a href="<?php echo $url;?>"><?php echo $lastposter;?></a></span>
								</td>
							</tr>
							<?php
						}
					}
					?>
				</table>
				<br>
				<?php
			} else echo 'Board is empty, no threads exist yet.<br>';

			///////////
			// Create thread button
			if ($charCount > 0) {
				if ($category['closed'] == 0  || $admin) {
					?>
					<table class="table">
					<tr><th>Create new thread</th></tr>
					<tr><td>
					<form action="" method="post">
						<div class="col-lg-3">	
						<input type="hidden" value="<?php echo $getCat; ?>" name="new_thread_category">
						<select name="new_thread_cid" class="form-control">
							<?php
							foreach($yourChars as $char) {
								echo "<option value='". $char['id'] ."'>". $char['name'] ."</option>";
							}
							?>
						</select>
						</div>
						<div class="col-lg-3">	
						<input type="submit" value="Create new thread" class="btn btn-primary">
						</div>
					</form>
					</td></tr>
					</table>
					<?php
				} else echo $alert.'This board is closed.</div>';
			} else echo $alert.'You must have a character on your account that is at least level '. $config['forum']['level'] .' to create new threads.</div>';
		} else echo $alert.'Your permission to access this board has been denied.<br>If you are trying to access a Guild Board, you need level: '. $config['forum']['level'] .'.</div>';

	}	
} else {

	//////////////////////
	// No category specified, show list of available categories
	if (!$admin) $categories = mysql_select_multi(
		"SELECT `id`, `name`, `access`, `closed`, `hidden`, `guild_id` FROM `znote_forum` WHERE `access`<='$yourAccess' ORDER BY `name`;");
		else $categories = mysql_select_multi("SELECT `id`, `name`, `access`, `closed`, `hidden`, `guild_id` FROM `znote_forum` ORDER BY `name`;");
	
	$guildboard = false;
	?>
	<ol class="breadcrumb">
		<li><strong>Forum</strong></li>
	</ol>

	<table class="znoteTable table table-striped table-hover" id="forumCategoryTable">
		<tr>
			<th width="35%"><?php echo $config['site_title']; ?> Boards</th>
			<th width="10%">Threads</th>
			<th width="10%">Posts</th>
			<th>Last Post</th>
			<?php
			$guild = false;
			foreach($charData as $char) {
				if ($char['guild'] > 0) $guild = true;
			}

			if ($admin || $guild) {
				if (!isset($guilds))  {
					$guilds = mysql_select_multi("SELECT `id`, `name` FROM `guilds` ORDER BY `name`;");
					$guilds[] = array('id' => '0', 'name' => 'No guild');
				}
				$guildName = array();
				foreach($guilds as $guild) {
					$guildName[$guild['id']] = $guild['name'];
				}
				if ($admin) {
					?>
					<th>Edit</th>
					<th>Delete</th>
					<?php
				}
			}
			?>
		</tr>
		<?php
		if ($categories !== false) {
			foreach ($categories as $category) {
				$access = true;
				if ($category['guild_id'] > 0) {
					$guildboard[] = $category;
					$access = false;
				}

				/*
				if ($guild) {
					foreach($charData as $char) {
						if ($category['guild_id'] == $char['guild']) $access = true;
					}
				}
				*/
				if ($access) {
				
					$url = url("forum.php?cat=". $category['id']);
					echo "<td>";
					if ($category['closed'] == 1) echo $config['forum']['closed'],' ';
					if ($category['hidden'] == 1) echo $config['forum']['hidden'],' ';
					if ($category['guild_id'] > 0) {
						echo "[". $guildName[$category['guild_id']] ."] ";
					}
					echo "<a href=". $url .">". $category['name'] ."</a></td>";

					$categoryid = $category['id'];
					$threads = mysql_select_multi("SELECT `id`, `player_name`, `updated` FROM `znote_forum_threads` WHERE `forum_id`=$categoryid ORDER BY `updated` ASC");
					(!empty($threads)) ? ($threadscount = count($threads)) : $threadscount = 0;
					$replies = 0;
					if (!empty($threads)) {
						foreach ($threads as $thread) {
							$threadid = $thread['id'];
							$posts = mysql_select_multi("SELECT `player_name`, `updated` FROM `znote_forum_posts` WHERE `thread_id`=$threadid ORDER BY `updated` DESC");
							if (!empty($posts)) { 
								$newreplies = count($posts);
								foreach($posts as $post) {
									$lastposter = $post['player_name'];
									$lastpostdate = $post['updated'];
									break; 
								}
							} else {
								$lastposter = $thread['player_name'];
								$lastpostdate = $thread['updated'];
								$newreplies = 0;
							}
							$replies = $replies + $newreplies;
						}
					}
					echo "<td>". $threadscount ."</td>";
					
					echo "<td>". $replies ."</td>";
					
					echo "<td>";
					if ($threadscount > 0) {
					$url = url("characterprofile.php?name=". $lastposter);
					echo "<span style='font-size:75%;'>". getClock($lastpostdate, true). "<br>";
					echo "by <a href='". $url ."'>". $lastposter ."</a></span>";
					} else {
					echo "<span style='font-size:75%;'>no post</span>";
					}
					echo "</td>";
					
					// Admin columns
					if ($admin) {
						?>
						<td>
							<form action="" method="post">
								<input type="hidden" name="admin_category_id" value="<?php echo $category['id']; ?>">
								<input type="submit" name="admin_category_edit" value="Edit" class="btn btn-success btn-xs">
							</form>
						</td>
						<td>
							<form action="" method="post">
								<input type="hidden" name="admin_category_id" value="<?php echo $category['id']; ?>">
								<input type="submit" name="admin_category_delete" value="Delete" class="btn btn-danger btn-xs needconfirmation">
							</form>
						</td>
						<?php
					}
					echo '</tr>';
				}
			}
		}
		?>
	</table>

	<?php
	if ($guildboard !== false && $guild || $guildboard !== false && $admin) {
		//
		?>
		<table class="table table-striped table-hover znoteTable" id="forumCategoryTable">
			<tr>
				<th width="35%">Guild Boards</th>
				<th width="10%">Threads</th>
				<th width="10%">Posts</th>
				<th>Last Post</th>
				<?php
				foreach($charData as $char) {
					if ($char['guild'] > 0) $guild = true;
				}

				if ($admin || $guild) {
					if (!isset($guilds))  {
						$guilds = mysql_select_multi("SELECT `id`, `name` FROM `guilds` ORDER BY `name`;");
						$guilds[] = array('id' => '0', 'name' => 'No guild');
					}
					$guildName = array();
					foreach($guilds as $guild) {
						$guildName[$guild['id']] = $guild['name'];
					}
					if ($admin) {
						?>
						<th>Edit</th>
						<th>Delete</th>
						<?php
					}
				}
				?>
			</tr>
			<?php
			$count = 0;
			foreach ($guildboard as $board) {
				$access = false;
				foreach($charData as $char) {
					if ($board['guild_id'] == $char['guild']) {
						$access = true;
						$count++;
					}
				}
				if ($access || $admin) {
				
					$url = url("forum.php?cat=". $board['id']);
					echo "<td>";
					if ($board['closed'] == 1) echo $config['forum']['closed'],' ';
					if ($board['hidden'] == 1) echo $config['forum']['hidden'],' ';
					if ($board['guild_id'] > 0) {
						/* echo "[". $board['name'] ."] "; */
					}
					echo "<a href=". $url .">". $guildName[$board['guild_id']] ."</a></td>";
					
					$categoryid = $board['id'];
					$threads = mysql_select_multi("SELECT `id`, `player_name`, `updated` FROM `znote_forum_threads` WHERE `forum_id`=$categoryid ORDER BY `updated` ASC");
					(!empty($threads)) ? ($threadscount = count($threads)) : $threadscount = 0;
					$replies = 0;
					if (!empty($threads)) {
						foreach ($threads as $thread) {
							$threadid = $thread['id'];
							$posts = mysql_select_multi("SELECT `player_name`, `updated` FROM `znote_forum_posts` WHERE `thread_id`=$threadid ORDER BY `updated` DESC");
							if (!empty($posts)) { 
								$newreplies = count($posts);
								foreach($posts as $post) {
									$lastposter = $post['player_name'];
									$lastpostdate = $post['updated'];
									break; 
								}
							} else {
								$lastposter = $thread['player_name'];
								$lastpostdate = $thread['updated'];
								$newreplies = 0;
							}
							$replies = $replies + $newreplies;
						}
					}
					echo "<td>". $threadscount ."</td>";
					
					echo "<td>". $replies ."</td>";
					
					echo "<td>";
					if ($threadscount > 0) {
					$url = url("characterprofile.php?name=". $lastposter);
					echo "<span style='font-size:75%;'>". getClock($lastpostdate, true). "<br>";
					echo "by <a href='". $url ."'>". $lastposter ."</a></span>";
					} else {
					echo "<span style='font-size:75%;'>no post</span>";
					}
					echo "</td>";
					
					// Admin columns
					if ($admin) {
						?>
						<td>
							<form action="" method="post">
								<input type="hidden" name="admin_category_id" value="<?php echo $board['id']; ?>">
								<input type="submit" name="admin_category_edit" value="Edit" class="btn btn-success btn-xs">
							</form>
						</td>
						<td>
							<form action="" method="post">
								<input type="hidden" name="admin_category_id" value="<?php echo $board['id']; ?>">
								<input type="submit" name="admin_category_delete" value="Delete" class="btn btn-danger btn-xs needconfirmation">
							</form>
						</td>
						<?php
					}
					echo '</tr>';
				}
			}
			if ($count == 0 && !$admin) echo '<tr><td>You don\'t have access to any guildboards.</td></tr>';
			?>
		</table>
		<?php
	}
	if ($admin) {
		?>
		<table class="table">
		<tr><th>Create board</th></tr>
		<tr><td>
		<form action="" method="post">
			<input type="text" name="admin_board_create_name" placeholder="Board name"><br><br>
			
			Required access: <select name="admin_board_create_access">
				<?php
				foreach($config['ingame_positions'] as $access => $name) {
					echo "<option value='$access'>$name</option>";
				}
				?>
			</select><br><br>

			Board closed: <select name="admin_board_create_closed">
				<option value="0">No</option>
				<option value="1">Yes</option>
			</select><br>
			
			Board hidden: <select name="admin_board_create_hidden">
				<option value="0">No</option>
				<option value="1">Yes</option>
			</select><br><br>

			Guild board: <select name="admin_board_create_guild_id">
				<?php
				foreach($guilds as $guild) {
					if ($guild['id'] == 0) echo "<option value='". $guild['id'] ."' selected>". $guild['name'] ."</option>";
					else echo "<option value='". $guild['id'] ."'>". $guild['name'] ."</option>";
				}
				?>
			</select><br><br>
			
			<input type="submit" value="Create Board" class="btn btn-primary">
		</form>
		</td></tr></table>
					<script src="engine/js/jquery-1.10.2.min.js" type="text/javascript"></script>
			<script>
			    $(document).ready(function(){
			        $(".needconfirmation").each(function(e){
			            $(this).click(function(e){
			                var itemname = $(this).attr("data-item-name");
							var r = confirm("Do you really want to DELETE this forum"+$('#admin_category_delete').find("#admin_category_id").text()+"?")
							if(r == false){
								e.preventDefault();
							}			
			            });
			        });
			    });
			</script>
		<?php
	}
}
include 'layout/overall/footer.php'; ?>