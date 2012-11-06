<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=continuum'.AMP.'method=update_settings')?>

<?php
$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('preference'), 'style' => 'width: 35%;'),
    lang('setting')
);

foreach ($settings as $key => $val)
{
	$label = '<label>'.lang($key).'</label>';
	
	if ($key == 'logging' || $key == 'log_anon_users')
	{
		$options = array(
			0 => lang('disabled'),
			1 => lang('enabled')
		);
	
		$val = form_dropdown($key, $options, $val);
	}
	
	else 
	{
		$val = form_input($key, $val);
	}
	
    $this->table->add_row($label, $val);
}

echo $this->table->generate();
?>

<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
<?php $this->table->clear()?>
<?=form_close()?>