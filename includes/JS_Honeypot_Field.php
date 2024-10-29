<?php
namespace AntispamForElementorForms;

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Add a JS Honeypot field to Elementor Forms.
 */
class JS_Honeypot_Field extends \ElementorPro\Modules\Forms\Fields\Field_Base {

    /**
     * @var string[] Scripts to enqueue if the field is included in the form.
     */
    public $depended_scripts = ['antispam-for-elementor-forms'];

    /**
     * Field constructor.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();

        add_action( 'elementor_pro/forms/render/item/asef-js-honeypot', [$this, 'container_args'], 10, 3 );
        add_action( 'elementor/element/form/section_form_fields/before_section_end', [ $this, 'update_controls' ] );
    }

    /**
     * Get field type.
     *
     * @return string Field type.
     */
    public function get_type() {
        return 'asef-js-honeypot';
    }

    /**
     * Get field name.
     *
     * @return string Field name.
     */
    public function get_name() {
        return esc_html__( 'JS Honeypot', 'antispam-for-elementor-forms' );
    }

    /**
     * Filter field container args.
     *
     * @param $item
     * @param $item_index
     * @param $widget
     * @return mixed
     */
    public function container_args( $item, $item_index, $widget ) {
        $id = 'field-group' . $item_index;
        $widget->set_render_attribute( $id, 'class', 'elementor-field-type-text asef-js-hp-container' );
        $widget->set_render_attribute( $id, 'data-label', $item['field_label'] ?: __( 'Please leave this field empty', 'antispam-for-elementor-forms' ) );
        $widget->set_render_attribute( $id, 'hidden' );
        $item['field_label'] = false;

        return $item;
    }

    /**
     * Do not render anything on page load for this field.
     *
     * @param mixed $item
     * @param mixed $item_index
     * @param mixed $form
     * @return void
     */
    public function render( $item, $item_index, $form ) {}

    /**
     * Field validation.
     *
     * @param array $field
     * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
     * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
     * @return void
     */
    public function validation( $field, $record, $ajax_handler ) {
		$field_settings = $this->get_field_from_record( $record );
		
        if( !$this->is_response_valid( ( $field_settings['delay'] ?? null ) ? absint( $field_settings['delay'] ) : null ) ) {
            $ajax_handler->add_error( $field['id'], esc_html__( 'Spam detection error.', 'antispam-for-elementor-forms' ) );
        } else {
            // If success - remove the field form list (don't send it in emails etc. )
            $record->remove_field( $field['id'] );
        }
    }

	/**
	 * Get full anti-spam field settings.
	 * 
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
	 * @return ?array
	 */
	public function get_field_from_record( \ElementorPro\Modules\Forms\Classes\Form_Record $record ): ?array {
		$fields = wp_list_filter( $record->get_form_settings( 'form_fields' ), [
			'field_type' => $this->get_type()
		] );
		
		if( $fields ) {
			return $fields[array_key_first( $fields )];
		}
		
		return null;
	}

	/**
	 * Check if the response is valid.
	 * 
	 * @param int|null $delay
	 * @return bool
	 */
	public function is_response_valid( ?int $delay ): bool {
		if( isset( $_POST['asef-js-hp'] ) ) {
			if( $delay ) {
				$submission_start_time = \DateTime::createFromFormat( 'U', (int) sanitize_text_field( $_POST['asef-js-hp'] ) );
				$now = new \DateTimeImmutable();
				$yesterday = $now->sub( new \DateInterval( 'P1D' ) );
				
				if( $submission_start_time && $submission_start_time >= $yesterday && (int) $submission_start_time->format( 'U' ) + $delay <= time() ) {
					return true;
				}
			} else {
				return true;
			}
		}
		
		return false;
	}

    /**
     * Hide surplus field settings.
     *
     * @param \Elementor\Widget_Base $widget
     * @return void
     */
    public function update_controls( \Elementor\Widget_Base $widget ) {
        $elementor = \ElementorPro\Plugin::elementor();

        $control_data = $elementor->controls_manager->get_control_from_stack( $widget->get_unique_name(), 'form_fields' );

        if( is_wp_error( $control_data ) ) {
            return;
        }

        foreach( $control_data['fields'] as $index => $field ) {
            if( 'required' === $field['name'] || 'width' === $field['name'] ) {
                $control_data['fields'][ $index ]['conditions']['terms'][] = [
                    'name' => 'field_type',
                    'operator' => '!in',
                    'value' => [
                        'asef-js-honeypot',
                    ],
                ];
            }
        }

	    $control_data['fields'] = $this->inject_field_controls( $control_data['fields'], [
		    'delay' => [
			    'name' => 'delay',
			    'label' => esc_html__( 'Delay', 'antispam-for-elementor-forms' ),
			    'description' => esc_html__( 'Set the minimum time, in seconds, the user must take to fill out this form.', 'antispam-for-elementor-forms' ),
			    'type' => \Elementor\Controls_Manager::NUMBER,
			    'condition' => [
				    'field_type' => $this->get_type(),
			    ],
			    'tab'          => 'content',
			    'inner_tab'    => 'form_fields_content_tab',
			    'tabs_wrapper' => 'form_fields_tabs',
		    ]
	    ] );

        $widget->update_control( 'form_fields', $control_data );
    }

}
