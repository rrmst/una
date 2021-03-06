<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaBaseView UNA Base Representation Classes
 * @{
 */

/**
 * @see BxDolView
 */
class BxBaseView extends BxDolView
{
    protected $_bCssJsAdded;
    protected $_sStylePrefix;
    protected $_sJsObjName;
    protected $_aHtmlIds;

    protected $_aElementDefaults;

    protected $_sTmplNameCounter;
    protected $_sTmplNameDoView;
    protected $_sTmplNameDoViewLabel;
    protected $_sTmplNameByList;

    public function __construct($sSystem, $iId, $iInit = 1)
    {
        parent::__construct($sSystem, $iId, $iInit);

        $this->_bCssJsAdded = false;

        $this->_sStylePrefix = 'bx-view';
        $this->_sJsObjName = 'oView' . bx_gen_method_name($sSystem, array('_' , '-')) . $iId;
        
        $sHtmlId = str_replace(array('_' , ' '), array('-', '-'), $sSystem) . '-' . $iId;
        $this->_aHtmlIds = array(
            'main' => 'bx-view-' . $sHtmlId,
            'counter' => 'bx-view-counter-' . $sHtmlId,
            'by_popup' => 'bx-view-by-popup-' . $sHtmlId
        );

        $this->_aElementDefaults = array(
            'show_do_view_as_button' => false,
            'show_do_view_as_button_small' => false,
            'show_do_view_icon' => true,
            'show_do_view_label' => false,
            'show_counter' => true
        );

        $this->_sTmplNameCounter = 'view_counter.html';
        $this->_sTmplNameDoView = 'view_do_view.html';
        $this->_sTmplNameDoViewLabel = 'view_do_view_label.html';
        $this->_sTmplNameByList = 'view_by_list.html';
    }

    public function addCssJs($bDynamicMode = false)
    {
    	if($bDynamicMode || $this->_bCssJsAdded)
    		return;

    	$this->_oTemplate->addJs(array('jquery.anim.js', 'BxDolView.js'));
        $this->_oTemplate->addCss(array('view.css'));

        $this->_bCssJsAdded = true;
    }

    public function getJsObjectName()
    {
        return $this->_sJsObjName;
    }

    public function getJsScript($bDynamicMode = false)
    {
        $aParams = array(
            'sObjName' => $this->_sJsObjName,
            'sSystem' => $this->getSystemName(),
            'iAuthorId' => $this->_getAuthorId(),
            'iObjId' => $this->getId(),
            'sRootUrl' => BX_DOL_URL_ROOT,
            'sStylePrefix' => $this->_sStylePrefix,
            'aHtmlIds' => $this->_aHtmlIds
        );
        $sCode = $this->_sJsObjName . " = new BxDolView(" . json_encode($aParams) . ");";

        if($bDynamicMode) {
			$sCode = "var " . $this->_sJsObjName . " = null; 
			$.getScript('" . bx_js_string($this->_oTemplate->getJsUrl('BxDolView.js'), BX_ESCAPE_STR_APOS) . "', function(data, textStatus, jqxhr) {
				bx_get_style('" . bx_js_string($this->_oTemplate->getCssUrl('view.css'), BX_ESCAPE_STR_APOS) . "');
				" . $sCode . "
        	}); ";
        }
        else
        	$sCode = "var " . $sCode;

        $this->addCssJs($bDynamicMode);
        return $this->_oTemplate->_wrapInTagJsCode($sCode);
    }

    public function getCounter($aParams = array())
    {
        $sJsObject = $this->getJsObjectName();

        $bShowDoViewAsButtonSmall = isset($aParams['show_do_view_as_button_small']) && $aParams['show_do_view_as_button_small'] == true;
        $bShowDoViewAsButton = !$bShowDoViewAsButtonSmall && isset($aParams['show_do_view_as_button']) && $aParams['show_do_view_as_button'] == true;
        $bAllowedViewViewViewers = $this->isAllowedViewViewViewers();

        $aView = $this->_oQuery->getView($this->getId());
        $sClass = $this->_sStylePrefix . '-counter';
        if($bShowDoViewAsButtonSmall)
            $sClass .= ' bx-btn-small-height';
        if($bShowDoViewAsButton)
            $sClass .= ' bx-btn-height';

        $sContent = (int)$aView['count'] > 0 ? $this->_getLabelCounter($aView['count']) : '';
        return $this->_oTemplate->parseHtmlByName($this->_sTmplNameCounter, array(
        	'bx_if:show_text' => array(
                'condition' => !$bAllowedViewViewViewers,
                'content' => array(
                    'bx_repeat:attrs' => array(
                        array('key' => 'id', 'value' => $this->_aHtmlIds['counter']),
                        array('key' => 'class', 'value' => $sClass),
                    ),
                    'content' => $sContent
                )
            ),
            'bx_if:show_link' => array(
                'condition' => $bAllowedViewViewViewers,
                'content' => array(
                    'bx_repeat:attrs' => array(
                        array('key' => 'id', 'value' => $this->_aHtmlIds['counter']),
                        array('key' => 'class', 'value' => $sClass),
                        array('key' => 'href', 'value' => 'javascript:void(0)'),
                        array('key' => 'onclick', 'value' => 'javascript:' . $sJsObject . '.toggleByPopup(this)'),
                        array('key' => 'title', 'value' => _t('_view_do_view_by'))
                    ),
                    'content' => $sContent
                )
            )
        ));
    }

    public function getElementBlock($aParams = array())
    {
        $aParams['usage'] = BX_DOL_VIEW_USAGE_BLOCK;

        return $this->getElement($aParams);
    }

    public function getElementInline($aParams = array())
    {
        $aParams['usage'] = BX_DOL_VIEW_USAGE_INLINE;

        return $this->getElement($aParams);
    }

    public function getElement($aParams = array())
    {
    	$aParams = array_merge($this->_aElementDefaults, $aParams);
    	$bDynamicMode = isset($aParams['dynamic_mode']) && $aParams['dynamic_mode'] === true;

    	$bAllowedView = $this->isAllowedView();
    	$bAllowedViewView = $this->isAllowedViewView();

        $bShowDoView = !isset($aParams['show_do_view']) || $aParams['show_do_view'] == true;
        $bShowDoViewAsButtonSmall = isset($aParams['show_do_view_as_button_small']) && $aParams['show_do_view_as_button_small'] == true;
        $bShowDoViewAsButton = !$bShowDoViewAsButtonSmall && isset($aParams['show_do_view_as_button']) && $aParams['show_do_view_as_button'] == true;
        $bShowCounter = isset($aParams['show_counter']) && $aParams['show_counter'] === true && $bAllowedViewView;

		$iObjectId = $this->getId();
		$iAuthorId = $this->_getAuthorId();
		$aView = $this->_oQuery->getView($iObjectId);
        $bCount = (int)$aView['count'] != 0;

        if(!$bAllowedView && (!$bAllowedViewView || !$bCount))
            return '';

        //--- Do View
        $bTmplVarsDoView = $bShowDoView && ($bCount || $bAllowedView);
        $aTmplVarsDoView = array();
        if($bTmplVarsDoView)
        	$aTmplVarsDoView = array(
				'style_prefix' => $this->_sStylePrefix,
				'do_view' => $this->_getDoView($aParams, $bAllowedView),
			);

        //--- Counter
        $bTmplVarsCounter = $bShowCounter && ($bCount || $bAllowedView);
        $aTmplVarsCounter = array();
        if($bTmplVarsCounter)
        	$aTmplVarsCounter = array(
				'style_prefix' => $this->_sStylePrefix,
				'bx_if:show_hidden' => array(
					'condition' => !$bCount,
					'content' => array()
				),
				'counter' => $this->getCounter($aParams)
        	);

		if(!$bTmplVarsDoView && !$bTmplVarsCounter)
			return '';

        $sTmplName = 'view_element_' . (!empty($aParams['usage']) ? $aParams['usage'] : BX_DOL_VIEW_USAGE_DEFAULT) . '.html';
        return $this->_oTemplate->parseHtmlByName($sTmplName, array(
            'style_prefix' => $this->_sStylePrefix,
            'html_id' => $this->_aHtmlIds['main'],
            'class' => $this->_sStylePrefix . ($bShowDoViewAsButton ? '-button' : '') . ($bShowDoViewAsButtonSmall ? '-button-small' : ''),
            'count' => $aView['count'],
        	'bx_if:show_do_view' => array(
        		'condition' => $bTmplVarsDoView,
        		'content' => $aTmplVarsDoView
        	),
        	'bx_if:show_counter' => array(
				'condition' => $bTmplVarsCounter,
				'content' => $aTmplVarsCounter
			),
            'script' => $this->getJsScript($bDynamicMode)
        ));
    }

    protected function _getDoView($aParams = array(), $bAllowedView = true)
    {
        $bShowDoViewAsButtonSmall = isset($aParams['show_do_view_as_button_small']) && $aParams['show_do_view_as_button_small'] == true;
        $bShowDoViewAsButton = !$bShowDoViewAsButtonSmall && isset($aParams['show_do_view_as_button']) && $aParams['show_do_view_as_button'] == true;
		$bDisabled = !$bAllowedView;

        $sClass = '';
		if($bShowDoViewAsButton)
			$sClass = 'bx-btn';
		else if ($bShowDoViewAsButtonSmall)
			$sClass = 'bx-btn bx-btn-small';

		if($bDisabled)
			$sClass .= $bShowDoViewAsButton || $bShowDoViewAsButtonSmall ? ' bx-btn-disabled' : 'bx-view-disabled';

        return $this->_oTemplate->parseHtmlByName($this->_sTmplNameDoView, array(
            'style_prefix' => $this->_sStylePrefix,
            'class' => $sClass,
            'title' => _t('_view_do_view'),
        	'bx_if:show_onclick' => array(
        		'condition' => !$bDisabled,
        		'content' => array(
        			'js_object' => $this->getJsObjectName()
        		)
        	),
            'do_view' => $this->_getLabelDo($aParams),
        ));
    }

    protected function _getLabelCounter($iCount)
    {
        return _t('_view_counter', $iCount);
    }

    protected function _getLabelDo($aParams = array())
    {
        return $this->_oTemplate->parseHtmlByName($this->_sTmplNameDoViewLabel, array(
        	'bx_if:show_icon' => array(
        		'condition' => isset($aParams['show_do_view_icon']) && $aParams['show_do_view_icon'] == true,
        		'content' => array(
        			'name' => $this->_getIconDo()
        		)
        	),
        	'bx_if:show_text' => array(
        		'condition' => isset($aParams['show_do_view_label']) && $aParams['show_do_view_label'] == true,
        		'content' => array(
        			'text' => _t($this->_getTitleDo())
        		)
        	)
        ));
    }

    protected function _getViewedBy()
    {
        $aTmplUsers = array();

        $aUserIds = $this->_oQuery->getPerformedBy($this->getId());
        foreach($aUserIds as $iUserId) {
            list($sUserName, $sUserUrl, $sUserIcon, $sUserUnit) = $this->_getAuthorInfo($iUserId);
            $aTmplUsers[] = array(
                'style_prefix' => $this->_sStylePrefix,
                'user_unit' => $sUserUnit
            );
        }

        if(empty($aTmplUsers))
            $aTmplUsers = MsgBox(_t('_Empty'));

        return $this->_oTemplate->parseHtmlByName($this->_sTmplNameByList, array(
            'style_prefix' => $this->_sStylePrefix,
            'bx_repeat:list' => $aTmplUsers
        ));
    }
}

/** @} */
