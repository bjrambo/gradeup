<?php

/**********************************
 **			뷰 클래스			 **
 ***********************************/

class gradeupView extends gradeup {

	//초기화
	function init()
	{
		// 사용자 템플릿 파일의 경로 설정 (skins)
		$template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
		if(!is_dir($template_path)||!$this->module_info->skin)
		{
			$this->module_info->skin = 'default';
			$template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
		}
		$this->setTemplatePath($template_path);

		//모듈정보구함
		$args = new stdClass();
		$args->module = 'gradeup'; //쿼리에 모듈명 변수전달
		$oModuleModel = getModel('module');
		$oGradeupModel = getModel('gradeup');
		$this->module_info = $oGradeupModel->getModuleInfo($args);
		$this->module_config = $oModuleModel->getModuleConfig('gradeup');
		//모듈정보세팅
		Context::set('module_config', $this->module_config);
		Context::set('module_info', $this->module_info);
	}

	//승인등업안내
	function dispGradeupConfirmGroup()
	{
		//모듈설정 가져오기
		$oModuleModel = getModel('module');
		$module_config = $oModuleModel->getModuleConfig('gradeup');

		//회원그룹 리스트 구함
		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups();

		//모델호출
		$oGradeupModel = getModel('gradeup');

		//로그인정보가져옴
		$logged_info = Context::get('logged_info');

		//조건이 있는 등업정보만 세팅
		$check_date = array();
		foreach($group_list as $key => $val)
		{
			$group = $module_config->gradeup_condition[$val->title];
			//변수추가
			$module_config->gradeup_condition[$val->title]['group_name'] = $oMemberModel->getGroup($group['gradeup_group_srl'])->title;
			$module_config->gradeup_condition[$val->title]['group_explain'] = $oMemberModel->getGroup($group['gradeup_group_srl'])->description;
			//등업방식 한글로변경
			if($group['gradeup_type'] == 'auto') 	$module_config->gradeup_condition[$val->title]['gradeup_type'] = '자동등업';
			if($group['gradeup_type'] == 'confirm') $module_config->gradeup_condition[$val->title]['gradeup_type'] = '승인등업';
			if($group['gradeup_type'] == 'term')	$module_config->gradeup_condition[$val->title]['gradeup_type'] = '기간등업';
			//등업조건 세부정보 구함
			$check_date = $group['gradeup_date']; //조회할 날짜
			$module_config->gradeup_condition[$val->title]['doc_count'] = $oGradeupModel->getMemberInfo('doc',$logged_info->member_srl,$check_date);
			$module_config->gradeup_condition[$val->title]['com_count'] = $oGradeupModel->getMemberInfo('com',$logged_info->member_srl,$check_date);
			$module_config->gradeup_condition[$val->title]['lv'] = $oGradeupModel->getMemberInfo('lv',$logged_info->member_srl);
			//충족여부 확인
			if($module_config->gradeup_condition[$val->title]['doc_count'] >= $group['gradeup_doc'] && $module_config->gradeup_condition[$val->title]['com_count'] >= $group['gradeup_com']  && $module_config->gradeup_condition[$val->title]['lv'] >= $group['gradeup_lv']) $module_config->gradeup_condition[$val->title]['condition'] = 'ok';
			//기본그룹인 경우 조건삭제
			if($val->is_default == 'Y' && $val->title == $module_config->gradeup_condition[$val->title]['group_name']) unset($module_config->gradeup_condition[$val->title]['gradeup_type']);
			//설정된 조건 없을시 등업정보에서 삭제
			if(!$group['gradeup_doc'] && !$group['gradeup_com'] && !$group['gradeup_lv']) unset($module_config->gradeup_condition[$val->title]);
		}

		//변수세팅
		Context::set('group_list', $group_list);
		Context::set('check_date', $check_date);
		Context::set('logged_info', $logged_info);
		Context::set('module_config', $module_config);

		//신청자 로그 가져옴
		$args = new stdClass();
		$args->list_count = '10';
		$args->page = Context::get('page');
		$args->order_type = Context::get('order_type');
		$args->s_state = 'ready'; //등업신청중인 로그만 가져옴
		if(!$args->order_type) $args->order_type = 'desc';
		$output = $oGradeupModel->getConfirmGroupLog($args);

		//그룹 보기 편하기 정리 (group_srl -> group_title)
		foreach($output->data as $key => $val)
		{
			$group_srl = explode('@', $val->old_group_srl);
			$output->data[$key]->old_group_srl_title = null;
			foreach($group_srl as $val)
			{
				$group_info = $oMemberModel->getGroup($val);
				if(!$output->data[$key]->old_group_srl_title)
				{
					$output->data[$key]->old_group_srl_title = $group_info->title;
				}
				else
				{
					$output->data[$key]->old_group_srl_title = $group_info->title.', '.$output->data[$key]->old_group_srl_title;
				}
			}
		}
		foreach($output->data as $key => $val)
		{
			$group_info = $oMemberModel->getGroup($val->add_group_srl);
			$val->add_group_srl_title = $group_info->title;
		}

		//로그 세팅
		Context::set('log_info',$output->data);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page_list', $output->data);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);

		//브라우저 제목 세팅
		Context::setBrowserTitle('등업안내');

		//템플릿 파일 설정
		$this->setTemplateFile('grade_explain');
	}
}
