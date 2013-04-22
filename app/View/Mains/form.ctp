<?php
echo $this->Form->create('Main', array('action' => 'form', 'type' => 'post'));
?>
<table>
  <tr>
	<th>項目</th>
	<th>入力</th>
  </tr>
  <tr>
	<td>User ID</td>
<td><? echo $this->Form->input('Member.user_id', array('type' => 'text', 'label'=>false, 'value' => isset($data)?$data['user_id']:'')); ?></td>
  </tr>
  <tr>
	<td>Password</td>
<td><? echo $this->Form->input('Member.password', array('type' => 'password', 'label'=>false, 'value' => isset($data)?$data['password']:'')); ?></td>
  </tr>
  <tr>
	<td>Last Name</td>
<td><? echo $this->Form->input('Member.last_name', array('type' => 'text', 'label'=>false, 'value' => isset($data)?$data['last_namae']:'')); ?></td>
  </tr>
  <tr>
	<td>First Name</td>
<td><? echo $this->Form->input('Member.first_name', array('type' => 'text', 'label'=>false, 'value' => isset($data)?$data['first_name']:'')); ?></td>
  </tr>
  <tr>
	<td>E-mail</td>
<td><? echo $this->Form->input('Member.e_mail', array('type' => 'text', 'label'=>false, 'value' => isset($data)?$data['e_mail']:'')); ?></td>
  </tr>
</table>
<table>
  <tr>
	<th colspan=2>Extensional Data</th>
  </tr>
  <tr>
	<td>Favarite Food</td>
	<td><? echo $this->Form->input('Member.favorite_food', array('type' => 'text', 'label'=>false, 'value' => isset($data)?$data['favorite_food']:'')); ?></td>
  </tr>
  <tr>
	<td>Favorite Number</td>
<td><? echo $this->Form->input('Member.favorite_num', array('type' => 'text', 'label' => false, 'value' => isset($data)?$data['favorite_num']:'')); ?></td>
  </tr>
</table>
<? echo $this->Form->end('Submit'); ?>