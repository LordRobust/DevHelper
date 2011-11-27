<?php
class DevHelper_Generator_Code_ControllerAdmin {
	public static function generate(array $addOn, DevHelper_Config_Base $config, array $dataClass, array $info) {
		$className = self::getClassName($addOn, $config, $dataClass);
		$variableName = strtolower(substr($dataClass['camelCase'], 0, 1)) . substr($dataClass['camelCase'], 1);
		$modelClassName = DevHelper_Generator_Code_Model::getClassName($addOn, $config, $dataClass);
		$commentAutoGeneratedStart = DevHelper_Generator_File::COMMENT_AUTO_GENERATED_START;
		$commentAutoGeneratedEnd = DevHelper_Generator_File::COMMENT_AUTO_GENERATED_END;
		$dataWriterClassName = DevHelper_Generator_Code_DataWriter::getClassName($addOn, $config, $dataClass);
		$otherDataClassStuff = '';
		$dataClassTitleField = empty($dataClass['title_field']) ? $dataClass['id_field'] : $dataClass['title_field'];
		$imageField = DevHelper_Generator_Db::getImageField($dataClass['fields']);
		
		$filterParams = array(); // this will be added later, by edit template generator (below)
		
		$templateList = DevHelper_Generator_Template::getTemplateTitle($addOn, $config, $dataClass, $dataClass['name'] . '_list');
		$templateEdit = DevHelper_Generator_Template::getTemplateTitle($addOn, $config, $dataClass, $dataClass['name'] . '_edit');
		$templateDelete = DevHelper_Generator_Template::getTemplateTitle($addOn, $config, $dataClass, $dataClass['name'] . '_delete');
		
		$viewListClassName = self::getViewClassName($addOn, $config, $dataClass, 'list');
		$viewEditClassName = self::getViewClassName($addOn, $config, $dataClass, 'edit');
		$viewDeleteClassName = self::getViewClassName($addOn, $config, $dataClass, 'delete');
		
		// create the phrases
		$phraseClassName = DevHelper_Generator_Phrase::getPhraseName($addOn, $config, $dataClass, $dataClass['name']);
		$phraseAdd = DevHelper_Generator_Phrase::getPhraseName($addOn, $config, $dataClass, $dataClass['name'] . '_add');
		$phraseEdit = DevHelper_Generator_Phrase::getPhraseName($addOn, $config, $dataClass, $dataClass['name'] . '_edit');
		$phraseDelete = DevHelper_Generator_Phrase::getPhraseName($addOn, $config, $dataClass, $dataClass['name'] . '_delete');
		$phraseSave = DevHelper_Generator_Phrase::getPhraseName($addOn, $config, $dataClass, $dataClass['name'] . '_save');
		$phraseConfirmDeletion = DevHelper_Generator_Phrase::getPhraseName($addOn, $config, $dataClass, $dataClass['name'] . '_confirm_deletion');
		$phrasePleaseConfirm = DevHelper_Generator_Phrase::getPhraseName($addOn, $config, $dataClass, $dataClass['name'] . '_please_confirm');
		$phraseNotFound = DevHelper_Generator_Phrase::getPhraseName($addOn, $config, $dataClass, $dataClass['name'] . '_not_found');
		$phraseNoResults = DevHelper_Generator_Phrase::getPhraseName($addOn, $config, $dataClass, $dataClass['name'] . '_no_results');
		
		DevHelper_Generator_Phrase::generatePhrase($addOn, $phraseClassName, $dataClass['camelCaseWSpace']);
		DevHelper_Generator_Phrase::generatePhrase($addOn, $phraseAdd, 'Add New ' . $dataClass['camelCaseWSpace']);
		DevHelper_Generator_Phrase::generatePhrase($addOn, $phraseEdit, 'Edit ' . $dataClass['camelCaseWSpace']);
		DevHelper_Generator_Phrase::generatePhrase($addOn, $phraseDelete, 'Delete ' . $dataClass['camelCaseWSpace']);
		DevHelper_Generator_Phrase::generatePhrase($addOn, $phraseSave, 'Save ' . $dataClass['camelCaseWSpace']);
		DevHelper_Generator_Phrase::generatePhrase($addOn, $phraseConfirmDeletion, 'Confirm Deletion of ' . $dataClass['camelCaseWSpace']);
		DevHelper_Generator_Phrase::generatePhrase($addOn, $phrasePleaseConfirm, 'Please confirm that you want to delete the following ' . $dataClass['camelCaseWSpace']);
		DevHelper_Generator_Phrase::generatePhrase($addOn, $phraseNotFound, 'The requested ' . $dataClass['camelCaseWSpace'] . ' could not be found');
		DevHelper_Generator_Phrase::generatePhrase($addOn, $phraseNoResults, 'No clues of ' . $dataClass['camelCaseWSpace'] . ' at this moment...');
		// finished creating pharses
		
		// create the templates
		$templateListTemplate = <<<EOF
<xen:title>{xen:phrase $phraseClassName}</xen:title>

<xen:topctrl>
	<a href="{xen:adminlink '{$info['routePrefix']}/add'}" class="button" accesskey="a">+ {xen:phrase $phraseAdd}</a>
</xen:topctrl>

<xen:require css="filter_list.css" />
<xen:require js="js/xenforo/filter_list.js" />

<xen:form action="{xen:adminlink '{$info['routePrefix']}'}" class="section">
	<xen:if is="{\$all{$dataClass['camelCase']}}">
		<h2 class="subHeading">
			<link rel="xenforo_template" type="text/html" href="filter_list_controls.html" />
			{xen:phrase $phraseClassName}
		</h2>
	
		<ol class="FilterList Scrollable">
			<xen:foreach loop="\$all{$dataClass['camelCase']}" value="\${$variableName}">
				<xen:listitem
					id="{\${$variableName}.{$dataClass['id_field']}}"
					href="{xen:adminlink '{$info['routePrefix']}/edit', \${$variableName}}"
					label="{\${$variableName}.{$dataClassTitleField}}"
					delete="{xen:adminlink '{$info['routePrefix']}/delete', \${$variableName}}" />
			</xen:foreach>
		</ol>
	
		<p class="sectionFooter">{xen:phrase showing_x_of_y_items, 'count=<span class="FilterListCount">{xen:count \$all{$dataClass['camelCase']}}</span>', 'total={xen:count \$all{$dataClass['camelCase']}}'}</p>
	<xen:else />
		<div class="noResults">{xen:phrase $phraseNoResults}</div>
	</xen:if>
</xen:form>
EOF;
		DevHelper_Generator_Template::generateAdminTemplate($addOn, $templateList, $templateListTemplate);
		// finished template_list
		
		$templateEditFormExtra = 'class="AutoValidator" data-redirect="yes"';
		
		$templateEditFields = '';
		foreach ($dataClass['fields'] as $field) {
			if ($field['name'] == $dataClass['id_field']) continue;
			if (empty($field['required'])) continue; // ignore non-required fields 
			if ($field['name'] == $imageField) continue; // ignore image field
			
			// queue this field for validation
			$filterParams[$field['name']] = $field['type'];
			
			if ($field['name'] == $dataClass['title_field']) {
				$fieldPhraseName = DevHelper_Generator_Phrase::generatePhraseAutoCamelCaseStyle($addOn, $config, $dataClass, $field['name']);				
				$templateEditFields .= <<<EOF

	<xen:textboxunit label="{xen:phrase $fieldPhraseName}:" name="{$field['name']}" value="{\${$variableName}.{$field['name']}}" data-liveTitleTemplate="{xen:if {\${$variableName}.{$dataClass['id_field']}},
		'{xen:phrase $phraseEdit}: <em>%s</em>',
		'{xen:phrase $phraseAdd}: <em>%s</em>'}" />
EOF;
				continue;
			}
			
			if (substr($field['name'], -3) == '_id') {
				// link to another dataClass?
				$other = substr($field['name'], 0, -3);
				if ($config->checkDataClassExists($other)) {
					// yeah!
					$otherDataClass = $config->getDataClass($other);
					$fieldPhraseName = DevHelper_Generator_Phrase::generatePhraseAutoCamelCaseStyle($addOn, $config, $otherDataClass, $otherDataClass['name']);				
					$templateEditFields .= <<<EOF

	<xen:selectunit label="{xen:phrase $fieldPhraseName}:" name="{$field['name']}" value="{\${$variableName}.{$field['name']}}">
		<xen:option value="">&nbsp;</xen:option>
		<xen:options source="\$all{$otherDataClass['camelCase']}" />
	</xen:selectunit>
EOF;
					$otherDataClassModelClassName = DevHelper_Generator_Code_Model::getClassName($addOn, $config, $otherDataClass);
					$otherDataClassStuff .= <<<EOF
'all{$otherDataClass['camelCase']}' => \$this->getModelFromCache('$otherDataClassModelClassName')->getList(),
EOF;
					continue;
				}
			}
			
			// special case with display_order
			if ($field['name'] == 'display_order') {
				$fieldPhraseName = DevHelper_Generator_Phrase::generatePhraseAutoCamelCaseStyle($addOn, $config, $dataClass, $field['name']);
				$templateEditFields .= <<<EOF

	<xen:spinboxunit label="{xen:phrase $fieldPhraseName}:" name="{$field['name']}" value="{\${$variableName}.{$field['name']}}" />
EOF;
				continue;
			}
			
			$fieldPhraseName = DevHelper_Generator_Phrase::generatePhraseAutoCamelCaseStyle($addOn, $config, $dataClass, $field['name']);
			$extra = '';
			if ($field['type'] == XenForo_DataWriter::TYPE_STRING AND (empty($field['length']) OR $field['length'] > 255)) {
				$extra .= ' rows="5"';
			}
			$templateEditFields .= <<<EOF

	<xen:textboxunit label="{xen:phrase $fieldPhraseName}:" name="{$field['name']}" value="{\${$variableName}.{$field['name']}}" $extra/>
EOF;
		}
		
		if ($imageField !== false) {
			$fieldPhraseImage = DevHelper_Generator_Phrase::generatePhraseAutoCamelCaseStyle($addOn, $config, $dataClass, 'image');
			$templateEditFormExtra = 'enctype="multipart/form-data"';
			
			$templateEditFields .= <<<EOF
	<xen:uploadunit label="{xen:phrase $fieldPhraseImage}:" name="image" value="">
		<div id="imageHtml">
			<xen:if is="{\${$variableName}.images}">
				<xen:foreach loop="\${$variableName}.images" key="\$imageSizeCode" value="\$image">
					<img src="{\$image}" alt="{\$imageSizeCode}" title="{\$imageSizeCode}" />
				</xen:foreach>
			</xen:if>
		</div>
	</xen:uploadunit>
EOF;
		}
		
		$templateEditTemplate = <<<EOF
<xen:title>{xen:if '{\${$variableName}.{$dataClass['id_field']}}', '{xen:phrase $phraseEdit}', '{xen:phrase $phraseAdd}'}</xen:title>

<xen:form action="{xen:adminlink '{$info['routePrefix']}/save'}" $templateEditFormExtra>

	$templateEditFields

	<xen:submitunit save="{xen:phrase $phraseSave}">
		<input type="button" name="delete" value="{xen:phrase $phraseDelete}"
			accesskey="d" class="button OverlayTrigger"
			data-href="{xen:adminlink '{$info['routePrefix']}/delete', \${$variableName}}"
			{xen:if '!{\${$variableName}.{$dataClass['id_field']}}', 'style="display: none"'}
		/>
	</xen:submitunit>
	
	<input type="hidden" name="{$dataClass['id_field']}" value="{\${$variableName}.{$dataClass['id_field']}}" />
</xen:form>
EOF;

		DevHelper_Generator_Template::generateAdminTemplate($addOn, $templateEdit, $templateEditTemplate);
		// finished template_edit
		
		$templateDeleteTemplate = <<<EOF
<xen:title>{xen:phrase $phraseConfirmDeletion}: {\${$variableName}.{$dataClassTitleField}}</xen:title>
<xen:h1>{xen:phrase $phraseConfirmDeletion}</xen:h1>

<xen:navigation>
	<xen:breadcrumb href="{xen:adminlink '{$info['routePrefix']}/edit', \${$variableName}}">{\${$variableName}.{$dataClassTitleField}}</xen:breadcrumb>
</xen:navigation>

<xen:require css="delete_confirmation.css" />

<xen:form action="{xen:adminlink '{$info['routePrefix']}/delete', \${$variableName}}" class="deleteConfirmForm formOverlay">

	<p>{xen:phrase $phrasePleaseConfirm}:</p>
	<strong><a href="{xen:adminlink '{$info['routePrefix']}/edit', \${$variableName}}">{\${$variableName}.{$dataClassTitleField}}</a></strong>

	<xen:submitunit save="{xen:phrase $phraseDelete}" />
	
	<input type="hidden" name="_xfConfirm" value="1" />
</xen:form>
EOF;
		DevHelper_Generator_Template::generateAdminTemplate($addOn, $templateDelete, $templateDeleteTemplate);
		
		// finished creating our templates
		
		$filterParams = DevHelper_Generator_File::varExport($filterParams, 2); // var export for the filter params
		$actionSaveImageCode = '';
		if ($imageField !== false) {
			$actionSaveImageCode = <<<EOF
		\$image = XenForo_Upload::getUploadedFile('image');
		if (!empty(\$image)) {
			\$dw->setImage(\$image);
		}
EOF;
		}
		
		$contents = <<<EOF
<?php

$commentAutoGeneratedStart

class {$info['controller']}_Generated extends XenForo_ControllerAdmin_Abstract {

	public function actionIndex() {
		\$model = \$this->_get{$dataClass['camelCase']}Model();
		\$all{$dataClass['camelCase']} = \$model->getAll{$dataClass['camelCase']}();
		
		\$viewParams = array(
			'all{$dataClass['camelCase']}' => \$all{$dataClass['camelCase']}
		);
		
		return \$this->responseView('$viewListClassName', '$templateList', \$viewParams);
	}
	
	public function actionAdd() {
		\$viewParams = array(
			'$variableName' => array(),
			$otherDataClassStuff
		);
		
		return \$this->responseView('$viewEditClassName', '$templateEdit', \$viewParams);
	}
	
	public function actionEdit() {
		\$id = \$this->_input->filterSingle('{$dataClass['id_field']}', XenForo_Input::UINT);
		\${$variableName} = \$this->_get{$dataClass['camelCase']}OrError(\$id);
		
		\$viewParams = array(
			'$variableName' => \${$variableName},
			$otherDataClassStuff
		);
		
		return \$this->responseView('$viewEditClassName', '$templateEdit', \$viewParams);
	}
	
	public function actionSave() {
		\$this->_assertPostOnly();
		
		\$id = \$this->_input->filterSingle('{$dataClass['id_field']}', XenForo_Input::UINT);

		\$dwInput = \$this->_input->filter($filterParams);
		
		\$dw = \$this->_get{$dataClass['camelCase']}DataWriter();
		if (\$id) {
			\$dw->setExistingData(\$id);
		}
		\$dw->bulkSet(\$dwInput);
		
$actionSaveImageCode
		
		\$this->_prepareDwBeforeSaving(\$dw);
		
		\$dw->save();

		return \$this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('{$info['routePrefix']}')
		);
	}
	
	public function actionDelete() {
		\$id = \$this->_input->filterSingle('{$dataClass['id_field']}', XenForo_Input::UINT);
		\${$variableName} = \$this->_get{$dataClass['camelCase']}OrError(\$id);
		
		if (\$this->isConfirmedPost()) {
			\$dw = \$this->_get{$dataClass['camelCase']}DataWriter();
			\$dw->setExistingData(\$id);
			\$dw->delete();

			return \$this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('{$info['routePrefix']}')
			);
		} else {
			\$viewParams = array(
				'$variableName' => \${$variableName}
			);

			return \$this->responseView('$viewDeleteClassName', '$templateDelete', \$viewParams);
		}
	}
	
	
	protected function _get{$dataClass['camelCase']}OrError(\$id, array \$fetchOptions = array()) {
		\$info = \$this->_get{$dataClass['camelCase']}Model()->get{$dataClass['camelCase']}ById(\$id, \$fetchOptions);
		
		if (empty(\$info)) {
			throw \$this->responseException(\$this->responseError(new XenForo_Phrase('$phraseNotFound'), 404));
		}
		
		return \$info;
	}
	
	protected function _get{$dataClass['camelCase']}Model() {
		return \$this->getModelFromCache('$modelClassName');
	}
	
	protected function _get{$dataClass['camelCase']}DataWriter() {
		return XenForo_DataWriter::create('$dataWriterClassName');
	}
	
	protected function _prepareDwBeforeSaving($dataWriterClassName \$dw) {
		// this method should be overriden if datawriter requires special treatments
	}
}

$commentAutoGeneratedEnd

class {$info['controller']} extends {$info['controller']}_Generated {
	// customized actions and whatelse should go here
}
EOF;

		return array($className, $contents);
	}
	
	public static function getClassName(array $addOn, DevHelper_Config_Base $config, array $dataClass) {
		return DevHelper_Generator_File::getClassName($addOn['addon_id'], 'ControllerAdmin_' . $dataClass['camelCase']);
	}
	
	public static function getViewClassName(array $addOn, DevHelper_Config_Base $config, array $dataClass, $view) {
		return DevHelper_Generator_File::getClassName($addOn['addon_id'], 'ViewAdmin_' . $dataClass['camelCase'] . '_' . ucwords($view));
	}
}