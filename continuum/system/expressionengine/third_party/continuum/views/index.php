<div id="continuum">

	<h3>Filters:</h3>
	
	<div class="visualise_buttons">
		<input type="submit" class="submit visualise" value="<?=lang('visualise')?>" />
		<input type="submit" class="submit clear" value="<?=lang('clear')?>" />
	</div>
	
	<div class="continuum_filters">
		<input type="hidden" id="base_url" value="<?=$base_url?>" />
		<input type="hidden" id="ajax_url" value="<?=$ajax_url?>" />
		<input type="hidden" id="last_log_id" value="<?=$last_log_id?>" />
		<input type="hidden" id="pending_log_ids" value="<?=$pending_log_ids?>" />
		
		<select name="user_id" class="chzn-select user_id">
			<option value=""><?=lang('all_users');?></option>
		<?php foreach($users as $key => $val): ?>
			<option value="<?=$key?>" <?=($key == $current_user_id ? 'selected' : '')?>><?=$val?></option>
		<?php endforeach; ?>
		</select>
		&nbsp;
		<select name="action" class="chzn-select">
			<option value=""><?=lang('all_actions');?></option>
		<?php foreach($actions as $key => $val): ?>
			<option value="<?=$key?>" <?=($key == $current_action ? 'selected' : '')?>><?=$val?></option>
		<?php endforeach; ?>
		</select>
		&nbsp;
		<select name="url_id" class="chzn-select url_id">
			<option value=""><?=lang('all_urls');?></option>
		<?php foreach($urls as $key => $val): ?>
			<option value="<?=$key?>" <?=($key == $current_url_id ? 'selected' : '')?>><?=$val?></option>
		<?php endforeach; ?>
		</select>
		&nbsp;
		<select name="limit" class="chzn-select">
			<option value="20" <?=($current_limit == 20 ? 'selected' : '')?>>20 <?=lang('results');?></option>
			<option value="50" <?=($current_limit == 50 ? 'selected' : '')?>>50 <?=lang('results');?></option>
			<option value="100" <?=($current_limit == 100 ? 'selected' : '')?>>100 <?=lang('results');?></option>
			<option value="500" <?=($current_limit == 500 ? 'selected' : '')?>>500 <?=lang('results');?></option>
		</select>
		&nbsp;
		<input type="submit" class="submit refresh_button" value="<?=lang('refresh')?>" />
	</div>
	
	<div id="d3"></div>
	
	<br/><br/>
	
	<?php 
		$this->table->set_template($cp_table_template);
		$this->table->set_heading(
			lang('user'),
			lang('action'),
			lang('url'),
			lang('time_on_page'),
			lang('timestamp'),
			lang('notes')
		);
		
		foreach($logs as $log)
		{
			$this->table->add_row(
				($log->member_id ? '<a href="'.BASE.AMP.'C=myaccount'.AMP.'id='.$log->member_id.'" target="_blank">' : '').($log->member_id ? $log->screen_name : 'User '.$log->user_id).($log->member_id ? '</a>' : '').' <a href="'.$base_url.'&user_id='.$log->user_id.'"><img src="'.$theme_folder_url.'images/filter.png" /></a>',
				'<span class="action '.$log->action_class.'">'.$log->action.'</span> '.'<a href="'.$base_url.'&action='.$log->action.'"><img src="'.$theme_folder_url.'images/filter.png" /></a>',
				'<a href="'.$log->absolute_url.'" target="_blank">'.$log->url.'</a> '.'<a href="'.$base_url.'&url_id='.$log->url_id.'"><img src="'.$theme_folder_url.'images/filter.png" /></a>',
				'<span id="log_'.$log->log_id.'">'.(($log->time_on_page != '') ? gmdate("H:i:s", $log->time_on_page) : (($log->action == 'Visit' OR $log->action == 'Landing') ? '<img src="'.$theme_folder_url.'images/loading.gif" />' : '')).'</span>',
				date("Y-m-d H:i:s T", $log->timestamp),
				str_replace('BASE', BASE, $log->notes)
			);
		}
		
		if (count($logs) == 0)
		{	
			$this->table->add_row('', '', '', '', '', '');
		}
	?>
	
	<?=$this->table->generate()?>
	
	<div style="float: right;">Viewing <span class="log_count"><?=count($logs)?></span> of <span class="total_logs"><?=$total_logs?></span> logs</div>

</div>