<?php
/**
 * @var WPTravelEngine\Builders\FormFields\FormField $travellers_form_fields
 * @var bool $show_title
 * @since 6.3.0
 */

if ( 'hide' === ( $args['attributes']['travellers'] ?? '' ) ) {
    return;
}
?>
<!-- Traveller's Details Form -->
<div class="wpte-checkout__box collapsible <?php echo $show_title ? 'open' : ''; ?>">
	<?php if ( $show_title ) : ?>
		<h3 class="wpte-checkout__box-title">
			<?php echo __( 'Traveller\'s Details', 'wp-travel-engine' ); ?>
			<button type="button" class="wpte-checkout__box-toggle-button">
				<svg>
					<use xlink:href="#chevron-down"></use>
				</svg>
			</button>
		</h3>
	<?php endif; ?>
	<div class="wpte-checkout__box-content">
		<div class="xxx hide">
			<?php
				print_r($travellers_form_fields);
			?>
		</div>
		<?php
		foreach ( $travellers_form_fields as $travellers_form_field ) {
			$travellers_form_field->render();
		}
		?>
	</div>
</div>
