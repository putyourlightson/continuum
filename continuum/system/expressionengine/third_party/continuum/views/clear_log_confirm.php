<p class="shun"><?=($entire ? lang('clear_entire_log_question') : lang('clear_anonymous_log_question'))?></p>

<p class="notice"><?=lang('action_can_not_be_undone')?></p>

<input type="submit" value="<?=lang('clear_log')?>" onclick="window.location = '<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=continuum'.AMP.'method=clear_log'.AMP.'entire='.$entire?>'" />