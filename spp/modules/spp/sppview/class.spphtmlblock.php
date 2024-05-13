<?php

namespace SPPMod;

class SPP_HTML_Block extends SPP_ViewTag {

	public $content;
    public $tag = 'div';

    public $attributes = array(
        'class' => 'block'
    );

    public $content_attributes = array(
        'class' => 'block-content'
    );

	/**
	 * Constructor
	 *
	 * @param string $content
	 */

	public function __construct($id, $tag ='div', $content = '', $attributes = array()) {
        parent::__construct($id, false);
        $this->tag = $tag;
        $this->attributes = $attributes;
        $this->addClass('block');
		if ($content != '')
            $this->content = $content;
	}

	public function setContent($content) {
		$this->content = $content;
	}

	public function __toString() {
		return $this->content;
	}

    public function render() {
        if (isset($this->attributes['id']))
            $this->attributes['id'] = $this->id;
        $this->content_attributes['id'] = $this->id . '-content';
        $html= '<' . $this->tag . SPP_ViewTag::RenderAttributes() . '>';
        $html.= '<div ' . SPP_ViewTag::renderAttributes() . '>' . $this->content . '</div>';
  
        $html=parent::render();
        $html.=$this->content;
        $html.= '</' . $this->tag . '>';
        $this->content = parent::render();
        return $this->content;
    }

    public function wrapContent($content) {
        return $this->content=$content;
    }

    public function renderContent() {
        return $this->content;
    }

    public function renderAttributes() {
        return parent::renderAttributes();
    }

    public function renderContentAttributes() {
        return parent::renderAttributes();
    }


}