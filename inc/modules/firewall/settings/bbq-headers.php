<?php
defined( 'ABSPATH' ) or die( 'Cheatin&#8217; uh?' );


$this->set_current_section( 'bbq_headers' );
$this->add_section( __( 'Bad Headers', 'secupress' ) );


$field_name = $this->get_field_name( 'user-agents-header' );
$main_field_name = $field_name;

$this->add_field(
	__( 'Block Bad User-Agents', 'secupress' ),
	array(
		'name'        => $field_name,
		'description' => __( 'You can easily add or remove bad keywords to adjust you needs.', 'secupress' ),
	),
	array(
		array(
			'type'         => 'checkbox',
			'name'         => $field_name,
			'label'        => __( 'Yes, protect my site from bad user-agents', 'secupress' ),
			'label_for'    => $field_name,
			'label_screen' => __( 'Yes, protect my site from bad user-agents', 'secupress' ),
		),
		array(
			'type'         => 'helper_description',
			'name'         => $field_name,
			'description'  => __( 'Bots are commonly using their own headers containing some known bad User-Agent. You can block them to avoid a crawl from their non desired services.', 'secupress' ),
		),
	)
);

$field_name = $this->get_field_name( 'user-agents-list' );

$this->add_field(
	__( 'User-Agents List', 'secupress' ),
	array(
		'name'        => $field_name,
		'description'  => __( 'We will automatically block any User-Agent containing any HTML tag in it or containing more than 255 characters.', 'secupress' ),
	),
	array(
		'depends_on'   => $main_field_name,
		array(
			'type'         => 'textarea',
			'name'         => $field_name,
			'label'        => __( 'None', 'secupress' ),
			'label_for'    => $field_name,
			'label_screen' => __( 'None', 'secupress' ),
		),
		array(
			'type'         => 'helper_description',
			'name'         => $field_name,
			'description' => __( 'Add or remove User-Agents you want to be blocked, or not.', 'secupress' ),
		),
	)
);