<?php

/**********************************
 **		  컨트롤러 클래스		 **
 ***********************************/

class gradeupController extends gradeup
{

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
	}

	//로그인 트리거 (자동등업, 기간제등업 관련)
	function triggerDoLoginAfter(&$obj)
	{
		//모듈설정 구함
		$oModuleModel = getModel('module');
		$module_config = $oModuleModel->getModuleConfig('gradeup');

		//gradeup 모델호출
		$oGradeupModel = getModel('gradeup');

		//기간제등업 확인
		$args = new stdClass();
		$args->s_member_srl = $obj->member_srl;
		$term_log = $oGradeupModel->getTermGroupLog($args)->data;
		if($term_log)
		{
			//등업일시 지난경우 등업 (이미 등업된 그룹인경우 패스)
			foreach($term_log as $key => $val)
			{
				//그룹이름 구함
				$oMemberModel = getModel('member');
				$group_name = $oMemberModel->getGroup($val->add_group_srl)->title;
				if($val->regdate < date('YmdHis') && !in_array($group_name, $obj->group_list))
				{
					//등업
					$oGradeupModel->gradeUpTerm($val->log_srl, 'term', $val->gradeup_add_type, $val->add_group_srl, $val->member_srl, $val->remain_date);
				}
			}
			//만료일 지난경우 복구
			foreach($term_log as $key => $val)
			{
				if($val->remain_date < date('YmdHis'))
				{
					//그룹복구 및 로그삭제
					$oGradeupModel->restoreGroup($val->log_srl, $obj->member_srl, $val->old_group_srl, $val->new_group_srl, $val->add_group_srl, $val->gradeup_add_type);
					//필요시 등업해제 관련 쪽지코드 삽입
				}
			}
		}

		//등업기능 사용시 작동
		if($module_config->gradeup_use != 'yes')
		{
			return;
		}

		//회원그룹 리스트 구함
		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups();

		//회원의 현재그룹을 새로구함 (중복등업방지)
		$member_groups = $oGradeupModel->getMemberGroups($obj->member_srl);

		//현재 그룹의 등급순서(우선순위) 구함, 그룹이 여러개일경우 가장 높은 우선순위 구함
		$priority = $oGradeupModel->getGradeupPriority($obj);

		//그룹별로 등업조건이 충족되는지 확인
		foreach($group_list as $key => $val)
		{
			//자동,기간제등업인 경우 조건확인
			if($module_config->gradeup_condition[$val->title]['gradeup_type'] != 'confirm')
			{
				$group = $module_config->gradeup_condition[$val->title];
				//이미 해당그룹인경우 패스
				if(!in_array($val->title, $member_groups))
				{
					//현재그룹보다 등급순서(우선순위)가 낮은경우 패스, 같은값이면 확인
					if($priority <= $group['gradeup_priority'])
					{
						//등업조건 있는경우 확인
						if($group['gradeup_doc'] || $group['gradeup_com'] || $group['gradeup_lv'])
						{
							//조건 없는항목은 통과
							if(!$group['gradeup_doc'])
							{
								$condition_doc = true;
							}
							if(!$group['gradeup_com'])
							{
								$condition_com = true;
							}
							if(!$group['gradeup_lv'])
							{
								$condition_lv = true;
							}

							//게시글조건 확인
							if($group['gradeup_doc'])
							{
								if($group['gradeup_date'])
								{
									$date = $group['gradeup_date'];
								}
								$condition_doc = $oGradeupModel->getCheckCondition('doc', $group['gradeup_doc'], $obj->member_srl, $date);
							}
							//댓글조건 확인
							if($group['gradeup_com'])
							{
								if($group['gradeup_date'])
								{
									$date = $group['gradeup_date'];
								}
								$condition_com = $oGradeupModel->getCheckCondition('com', $group['gradeup_com'], $obj->member_srl, $date);
							}
							//레벨조건 확인
							if($group['gradeup_lv'])
							{
								$condition_lv = $oGradeupModel->getCheckCondition('lv', $group['gradeup_lv'], $obj->member_srl);
							}
							//조건충족시 등업
							if($condition_doc && $condition_com && $condition_lv)
							{
								$oGradeupModel->gradeUp($group['gradeup_type'], $group['gradeup_add_type'], $group['gradeup_group_srl'], $obj->member_srl, $group['gradeup_remain_date']);
							}
						}
					}
				}
			}
		}
	}

	//승인등업신청
	function procGradeupConfirmGroup()
	{
		//입력값을 모두 받음
		$obj = Context::getRequestVars();

		//회원정보 구함
		$logged_info = Context::get('logged_info');

		//회원정보 없을시 리턴
		if(!$logged_info)
		{
			return new Object(-1, '로그인이후 사용이 가능합니다.');
		}

		//모듈설정 구함
		$oModuleModel = getModel('module');
		$module_config = $oModuleModel->getModuleConfig('gradeup');

		//gradeup 모델호출
		$oGradeupModel = getModel('gradeup');

		//이미 신청한경우 리턴
		$args = new stdClass();
		$args->s_member_srl = $logged_info->member_srl;
		$args->s_state = 'ready';
		$log = $oGradeupModel->getConfirmGroupLog($args)->data;
		if($log)
		{
			$message = '이미 등업 신청내역이 있습니다.';
			$oGradeupModel = getModel('gradeup');
			$oGradeupModel->alertMsg($message);
		}

		//기본변수정리
		$args = new stdClass();
		$args->member_srl = $logged_info->member_srl;
		$args->old_group_srl = $oGradeupModel->getMemberGroupSrl($logged_info->member_srl);
		$args->add_group_srl = $obj->add_group_srl;
		$args->user_text = $obj->user_text ? strip_tags($obj->user_text) : '열심히 활동하겠습니다.';	//입력된 메세지(각오)없는경우
		$args->regdate = date('YmdHis');
		$args->state = 'ready';

		//조건변수정리
		foreach($module_config->gradeup_condition as $key => $val)
		{
			if($val['gradeup_group_srl'] == $obj->add_group_srl)
			{
				//혹시나... 조건설정이 없을시 리턴
				if(!$val['gradeup_doc'] && !$val['gradeup_com'] && !$val['gradeup_lv']) return;
				//조건 없는항목은 통과
				if(!$val['gradeup_doc']) $condition_doc = true;
				if(!$val['gradeup_com']) $condition_com = true;
				if(!$val['gradeup_lv']) $condition_lv = true;
				//조건기간 변수입력
				$args->condition_date = $val['gradeup_date'];
				//게시글조건 확인
				if($val['gradeup_doc'])
				{
					$args->condition_doc = $oGradeupModel->getMemberInfo('doc',$logged_info->member_srl,$val['gradeup_date']);
					$condition_doc = $oGradeupModel->getCheckCondition('doc', $val['gradeup_doc'], $logged_info->member_srl, $val['gradeup_date']);
				}
				//댓글조건 확인
				if($val['gradeup_com'])
				{
					$args->condition_com = $oGradeupModel->getMemberInfo('com',$logged_info->member_srl,$val['gradeup_date']);
					$condition_com = $oGradeupModel->getCheckCondition('com', $val['gradeup_com'], $logged_info->member_srl, $val['gradeup_date']);
				}
				//레벨조건 확인
				if($val['gradeup_lv'])
				{
					$args->condition_lv = $oGradeupModel->getMemberInfo('lv',$logged_info->member_srl);
					$condition_lv = $oGradeupModel->getCheckCondition('lv', $val['gradeup_lv'], $logged_info->member_srl);
				}
				//조건충족시 등업
				if($condition_doc && $condition_com && $condition_lv)
				{
					$args->condition_result = 'ok';
				}
				else
				{
					$args->condition_result = 'no';
				}
			}
		}
		debugPrint($args);
		//db입력
		executeQuery('gradeup.insertGradeUpConfirmGroup',$args);

		//신청완료 메세지세팅
		$message = '정상적으로 등업신청이 되었습니다.';
		$oGradeupModel = getModel('gradeup');
		$oGradeupModel->alertMsg($message);
	}


	//승인등업처리
	function procGradeupConfirmGroupProcess()
	{
		//입력값을 모두 받음
		$obj = Context::getRequestVars();

		//log_srl 없을시 리턴
		if(!$obj->log_srl) return;

		//모듈설정 구함
		$oModuleModel = getModel('module');
		$module_config = $oModuleModel->getModuleConfig('gradeup');

		//회원정보구함
		$logged_info = Context::get('logged_info');

		//승인시
		if($obj->state == 'ok')
		{
			//변수설정
			$args = new stdClass();
			$args->log_srl = $obj->log_srl;
			$args->state = 'ok';

			//state값 업데이트
			executeQuery('gradeup.updateConfirmGroupLog',$args);

			//모델호출
			$oGradeupModel = getModel('gradeup');

			//신청정보 가져옴
			$args = new stdClass();
			$args->s_log_srl = $obj->log_srl;
			$output = $oGradeupModel->getConfirmGroupLog($args)->data[1];

			//변수입력
			foreach($module_config->gradeup_condition as $key => $val)
			{
				if($val['gradeup_group_srl'] == $output->add_group_srl)
				{
					$add_type = $val['gradeup_add_type'];	//그룹추가방식
				}
			}
			$member_srl = $output->member_srl;		//회원번호
			$group_srl = $output->add_group_srl;	//신청그룹

			//등업 및 로그기록
			$oGradeupModel->gradeUp('confirm', $add_type, $group_srl, $member_srl);

			//메세지세팅
			$message = '승인처리 되었습니다.';
			$oGradeupModel->alertMsg($message);
		}

		//거절시
		if($obj->state == 'no')
		{
			//변수설정
			$args = new stdClass();
			$args->log_srl = $obj->log_srl;
			$args->state = 'no';

			//state값 업데이트
			executeQuery('gradeup.updateConfirmGroupLog',$args);

			//모델호출
			$oGradeupModel = &getModel('gradeup');

			//거절메세지 있을시 발송
			if($obj->refuse_msg_content)
			{
				//변수세팅
				$sender_srl = $module_config->garadeup_msg_member_srl ? $module_config->garadeup_msg_member_srl : $logged_info->member_srl;	//보내는사람
				$receiver_srl = $obj->member_srl;		//받는사람
				$title = $obj->refuse_msg_title ? $obj->refuse_msg_title : '등업신청이 거절되었습니다.';	//쪽지제목
				$content = $obj->refuse_msg_content;	//쪽지내용
				//쪽지발송
				$oCommunicationController = &getController('communication');
				$oCommunicationController->sendMessage($sender_srl, $receiver_srl, $title, $content);
			}

			//메세지세팅
			$message = '거절처리 하였습니다.';
			$oGradeupModel->alertMsg($message);
		}
	}

	//승인등업취소
	function procGradeupConfirmGroupCancel()
	{
		//회원정보 구함
		$logged_info = Context::get('logged_info');

		//회원정보 없을시 리턴
		if(!$logged_info)
		{
			$this->setMessage('로그인후 이용가능합니다.');
		}
		if(!$logged_info)
		{
			return $this->setRedirectUrl(getNotEncodedUrl('','module','gradeup','act','dispGradeupConfirmGroup'));
		}

		//변수세팅
		$args = new stdClass();
		$args->member_srl = $logged_info->member_srl;
		$args->state = 'ready';
		$output = executeQuery('gradeup.deleteConfirmLog',$args);
		//완료메세지 세팅
		$message = '등업신청이 취소 되었습니다.';
		$oGradeupModel = getModel('gradeup');
		$oGradeupModel->alertMsg($message);
	}
}
