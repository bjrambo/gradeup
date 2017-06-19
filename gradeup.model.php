<?php

/**********************************
 **			모델 클래스			 **
 ***********************************/

class gradeupModel extends gradeup
{

	//초기화
	function init()
	{
	}

	//모듈정보구함
	function getModuleInfo($args)
	{
		$output = executeQuery('gradeup.get_moduleInfo',$args);
		if(!$output->data->module_srl) return;
		$oModuleModel = &getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($output->data->module_srl);
		return $module_info;
	}

	//등업로그 구함
	function getGradeLog($args)
	{
		$output = executeQueryArray('gradeup.getGradeLog',$args);
		return $output;
	}

	//기간제 등업 로그 구함
	function getTermGroupLog($args)
	{
		$output = executeQueryArray('gradeup.getTermGroupLog',$args);
		return $output;
	}

	//승인 등업 로그 구함
	function getConfirmGroupLog($args)
	{
		$output = executeQueryArray('gradeup.getConfirmGroupLog',$args);
		return $output;
	}

	//팝업 안내메세지
	function alertMsg($message)
	{
		//입력된 메세지 없으면 리턴
		if(!$message) return;
		header("Content-Type: text/html; charset=UTF-8"); //헤더설정 직접 해주거나(한글인코딩) 아래주석 제거하거나 선택적 사용
		//htmlHeader();
		alertScript($message);
		echo '<script type="text/javascript">history.back()</script>';
		//htmlFooter();
		Context::close();
		exit;
	}

	//그룹정보 구함
	function getMemberGroups($member_srl)
	{
		//회원번호 없을시 리턴
		if(!$member_srl) return array();
		//그룹정보구함
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQueryArray('member.getMemberGroups', $args);
		$group_list = $output->data;
		if(!$group_list) return array();
		foreach($group_list as $group)
		{
			$result[$group->group_srl] = $group->title;
		}
		return $result;
	}

	//등업순위(우선순위) 확인
	function getGradeupPriority($logged_info)
	{
		//회원정보없을시 리턴
		if(!$logged_info) return;

		//모듈설정 구함
		$oModuleModel = &getModel('module');
		$module_config = $oModuleModel->getModuleConfig('gradeup');

		//회원이 속한 그룹의 우선순위를 모두 구한후 가장 높은 우선순위값 반환
		$priority = null;
		foreach($logged_info->group_list as $key => $val)
		{
			if(!$priority)
			{
				$priority =	$module_config->gradeup_condition[$val]['gradeup_priority'];
			}
			else
			{
				if($priority < $module_config->gradeup_condition[$val]['gradeup_priority']) $priority = $module_config->gradeup_condition[$val]['gradeup_priority'];
			}
		}
		return $priority;
	}

	//게시글갯수,댓글갯수,레벨 조회
	function getMemberInfo($type,$member_srl,$regdate = null)
	{
		//조건변수 없을시 리턴
		if(!$type || !$member_srl) return;

		//변수대입
		$args = new stdClass();
		$args->member_srl = $member_srl;
		if($regdate) $args->more_regdate = date('Ymd',strtotime('-'.$regdate.' day'));

		//게시글수 조회
		if($type == 'doc')
		{
			$doc_count = executeQuery('gradeup.getDocumentCount',$args)->data->count;
			return $doc_count;
		}
		//댓글수 조회
		if($type == 'com')
		{
			$com_count = executeQuery('gradeup.getCommentCount',$args)->data->count;
			return $com_count;
		}
		//레벨조회
		if($type == 'lv')
		{
			$oPointModel = getModel('point');
			$member_point = $oPointModel->getPoint($member_srl);
			$oModuleModel = getModel('module');
			$point_config = $oModuleModel->getModuleConfig('point');
			$member_level = $oPointModel->getLevel($member_point, $point_config->level_step);
			return $member_level;
		}
	}

	//등업조건(글,댓글,레벨) 확인
	function getCheckCondition($type,$condition,$member_srl,$date)
	{
		//조건변수 없을시 리턴
		if(!$type || !$condition || !$member_srl) return;

		//변수대입
		$args = new stdClass();
		$args->member_srl = $member_srl;
		if($date) $args->more_regdate = date('Ymd',strtotime('-'.$date.' day'));

		//게시글 조건 확인
		if($type == 'doc')
		{
			$doc_count = executeQuery('gradeup.getDocumentCount',$args)->data->count;
			if($doc_count >= $condition)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		//댓글 조건 확인
		if($type == 'com')
		{
			$com_count = executeQuery('gradeup.getCommentCount',$args)->data->count;
			if($com_count >= $condition)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		//레벨 조건 확인
		if($type == 'lv')
		{
			$oPointModel = &getModel('point');
			$member_point = $oPointModel->getPoint($member_srl);
			$oModuleModel = &getModel('module');
			$point_config = $oModuleModel->getModuleConfig('point');
			$member_level = $oPointModel->getLevel($member_point, $point_config->level_step);
			if($member_level >= $condition)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}

	//적용중인 group_srl 구함 (여러개 일시 @ 로 묶음)
	function getMemberGroupSrl($member_srl)
	{
		//회원번호 없을시 리턴
		if(!$member_srl) return;

		//현재 적용중인 그룹구함
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$member_groups = executeQueryArray('gradeup.getMemberGroup',$args)->data;

		//그룹갯수가 2개이상일 경우 @로 묶음
		if(count($member_groups) > 1)
		{
			foreach($member_groups as $key => $val)
			{
				if(!$group_srl)
				{
					$group_srl = $val->group_srl;
				}
				else
				{
					$group_srl = $group_srl.'@'.$val->group_srl;
				}
			}
		}
		else
		{
			foreach($member_groups as $key => $val)
			{
				$group_srl = $val->group_srl;
			}
		}
		return $group_srl;
	}


	//기간제 등업 만료로 인한 그룹복구 및 로그삭제
	function restoreGroup($log_srl, $member_srl, $old_group_srl, $new_group_srl, $add_group_srl, $gradeup_add_type)
	{
		//변수값 없을시 리턴
		if(!$log_srl || !$member_srl || !$old_group_srl || !$new_group_srl || !$add_group_srl) return;

		//등업기간동안 그룹이 추가로 변경되었을수도 있으므로 그룹복구 당시의 회원그룹을 기준으로 하되 등업신청 당시의 그룹도 예비로 준비함
		$old_group_srl_array = explode('@',$old_group_srl);	//등업전그룹
		$now_group_srl_array = explode('@',$this->getMemberGroupSrl($member_srl));  //현재회원그룹
		$grade_group_srl_array = array($add_group_srl);  //등업대상그룹
		$change_group_srl_array = array_diff ($now_group_srl_array, $grade_group_srl_array);  //현재회원그룹에서 등업대상그룹을 뺀값

		//등업그룹삭제
		$d_args = new stdClass();
		$d_args->member_srl = $member_srl;
		executeQuery('gradeup.deleteMemberGroup',$d_args);

		//이전그룹복구
		$g_args = new stdClass();
		$g_args->member_srl = $member_srl;
		//change_group_srl_array 값이 있는경우
		if($change_group_srl_array)
		{
			foreach($change_group_srl_array as $key => $val)
			{
				$g_args->group_srl = $val;
				executeQuery('gradeup.insertMemberGroup',$g_args);
			}
		}
		else
		{
			//change_group_srl_array 없을시 등업전그룹으로 복구
			foreach($old_group_srl_array as $key => $val)
			{
				$g_args->group_srl = $val;
				executeQuery('gradeup.insertMemberGroup',$g_args);
			}
		}

		//기간제 등업 로그삭제
		$l_args = new stdClass();
		$l_args->log_srl = $log_srl;
		executeQuery('gradeup.deleteTermLog',$l_args);

		//등업로그에 기록
		$log_args = new stdClass();
		$log_args->member_srl = $member_srl;
		$log_args->gradeup_type = 'restore';
		$log_args->add_group_srl = $add_group_srl;
		$log_args->old_group_srl = $new_group_srl;
		$log_args->new_group_srl = $old_group_srl;
		$log_args->gradeup_add_type = $gradeup_add_type;
		$log_args->regdate = date('YmdHis');
		executeQuery('gradeup.insertGradeUp',$log_args);

		//캐시초기화해줌 (안해주면 경우에 따라 적용이 늦게되거나 꼬일수 있음)
		$oMemberController = getController('member');
		$oMemberController->_clearMemberCache($member_srl);
	}


	//기간제등업 회원추가로 인한 등업의 경우
	function gradeUpTerm($log_srl, $gradeup_type, $add_type, $group_srl, $member_srl, $remain_date){
		//변수 없을시 리턴
		if(!$log_srl || !$gradeup_type || !$add_type || !$group_srl || !$member_srl) return;

		//등업전 group_srl (로그기록용)
		$old_group_srl = $this->getMemberGroupSrl($member_srl);

		//초기화일경우
		if($add_type == 'reset')
		{
			//그룹삭제
			$d_args = new stdClass();
			$d_args->member_srl = $member_srl;
			executeQuery('gradeup.deleteMemberGroup',$d_args);
		}

		//그룹추가
		$inset_args= new stdClass();
		$inset_args->member_srl = $member_srl;
		$inset_args->group_srl = $group_srl;
		executeQuery('gradeup.insertMemberGroup',$inset_args);

		//등업후 group_srl (로그기록용)
		$new_group_srl = $this->getMemberGroupSrl($member_srl);

		//등업로그에 기록
		$log_args = new stdClass();
		$log_args->member_srl = $member_srl;
		$log_args->gradeup_type = $gradeup_type;
		$log_args->old_group_srl = $old_group_srl;
		$log_args->new_group_srl = $new_group_srl;
		$log_args->add_group_srl = $group_srl;
		$log_args->gradeup_add_type = $add_type;
		$log_args->regdate = date('YmdHis');
		$log_args->remain_date = $remain_date;
		executeQuery('gradeup.insertGradeUp',$log_args);

		// new_group_srl 값 업데이트 해줌
		$args = new stdClass();
		$args->log_srl = $log_srl;
		$args->new_group_srl = $new_group_srl;
		executeQuery('gradeup.updateTermGroupLog',$args);

		//쪽지용변수 추가
		$remain_date = date('Y년 m월 d일 H시 i분 s초',strtotime($remain_date));

		//쪽지발송
		$this->gradeupSendMessage($gradeup_type, $group_srl, $member_srl, $remain_date);

		//캐시초기화해줌 (안해주면 경우에 따라 적용이 늦게되거나 꼬일수 있음)
		$oMemberController = getController('member');
		$oMemberController->_clearMemberCache($member_srl);
	}


	//등업 및 로그 기록
	function gradeUp($gradeup_type, $add_type, $group_srl, $member_srl, $remain_date = null)
	{
		//변수 없을시 리턴
		if(!$gradeup_type || !$add_type || !$group_srl || !$member_srl) return;

		//등업전 group_srl (로그기록용)
		$old_group_srl = $this->getMemberGroupSrl($member_srl);

		//초기화일경우
		if($add_type == 'reset')
		{
			//그룹삭제
			$d_args = new stdClass();
			$d_args->member_srl = $member_srl;
			executeQuery('gradeup.deleteMemberGroup',$d_args);
		}

		//그룹추가
		$insert_args = new stdClass();
		$insert_args->member_srl = $member_srl;
		$insert_args->group_srl = $group_srl;
		executeQuery('gradeup.insertMemberGroup',$insert_args);

		//등업후 group_srl (로그기록용)
		$new_group_srl = $this->getMemberGroupSrl($member_srl);

		//등업로그에 기록
		$log_args = new stdClass();
		$log_args->member_srl = $member_srl;
		$log_args->gradeup_type = $gradeup_type;
		$log_args->old_group_srl = $old_group_srl;
		$log_args->new_group_srl = $new_group_srl;
		$log_args->add_group_srl = $group_srl;
		$log_args->gradeup_add_type = $add_type;
		$log_args->regdate = date('YmdHis');
		if($remain_date) $log_args->remain_date = date('YmdHis',strtotime('+'.$remain_date.' day'));
		executeQuery('gradeup.insertGradeUp',$log_args);

		//기간제 등업인 경우 추가로 기록남김 (만료일 미지정시 패스)
		if($gradeup_type == 'term' && $remain_date)
		{
			$gu_args = new stdClass();
			$gu_args->member_srl = $member_srl;
			$gu_args->old_group_srl = $old_group_srl;
			$gu_args->new_group_srl = $new_group_srl;
			$gu_args->add_group_srl = $group_srl;
			$gu_args->gradeup_add_type = $add_type;
			$gu_args->regdate = date('YmdHis');
			$gu_args->remain_date = date('YmdHis',strtotime('+'.$remain_date.' day'));
			executeQuery('gradeup.insertGradeUpTermGroup',$gu_args);
			//쪽지용변수 추가
			$remain_date = date('Y년 m월 d일 H시 i분 s초',strtotime('+'.$remain_date.' day'));
		}

		//쪽지발송
		$this->gradeupSendMessage($gradeup_type, $group_srl, $member_srl, $remain_date);

		//캐시초기화해줌 (안해주면 경우에 따라 적용이 늦게되거나 꼬일수 있음)
		$oMemberController = getController('member');
		$oMemberController->_clearMemberCache($member_srl);
	}


	//쪽지발송
	function gradeupSendMessage($gradeup_type, $group_srl, $member_srl, $remain_date)
	{
		$oModuleModel = getModel('module');
		$module_config = $oModuleModel->getModuleConfig('gradeup');
		//쪽지기능 미사용시 리턴
		if($module_config->gradeup_msg_use != 'yes')
		{
			return;
		}

		//그룹이름 구함
		$oMemberModel = getModel('member');
		$group_name = $oMemberModel->getGroup($group_srl)->title;

		//기본변수 입력
		$sender_srl = $module_config->garadeup_msg_member_srl;	//보내는사람
		$receiver_srl = $member_srl;	//받는사람
		$title = $module_config->garadeup_msg_title;	//쪽지제목
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
		//자동등업인경우
		if($gradeup_type == 'auto')
		{
			$gradeup_auto_msg = str_replace(array("\r\n","\r","\n"),'',$module_config->gradeup_auto_msg); 	//엔터제거
			$gradeup_auto_msg = str_replace(array('[nick_name]','[group_name]','[enter]'),array($member_info->nick_name, $group_name,'<br/>'),$gradeup_auto_msg);
			$content = $gradeup_auto_msg;
		}
		//기간제등업인경우
		if($gradeup_type == 'term')
		{
			$gradeup_term_msg = str_replace(array("\r\n","\r","\n"),'',$module_config->gradeup_term_msg); 	//엔터제거
			$gradeup_term_msg = str_replace(array('[nick_name]','[group_name]','[remain_date]','[enter]'),array($member_info->nick_name, $group_name, $remain_date, '<br/>'),$gradeup_term_msg);
			$content = $gradeup_term_msg;
		}
		//승인등업인경우
		if($gradeup_type == 'confirm')
		{
			$gradeup_confirm_msg = str_replace(array("\r\n","\r","\n"),'',$module_config->gradeup_confirm_msg); 	//엔터제거
			$gradeup_confirm_msg = str_replace(array('[nick_name]','[group_name]','[enter]'),array($member_info->nick_name, $group_name, '<br/>'),$gradeup_confirm_msg);
			$content = $gradeup_confirm_msg;
		}
		//쪽지발송
		$oCommunicationController = getController('communication');
		$oCommunicationController->sendMessage($sender_srl, $receiver_srl, $title, $content);
	}
}
