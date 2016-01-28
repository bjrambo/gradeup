//////////////////////////////////////////////////////////////////////////////////////
////////////////////////				로그 삭제		 /////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////

// 선택로그삭제
function jsDeleteGradeConfirmLog() {
    var log_data = xGetElementById("log_data_table");
    var log_srl = new Array();

    if(typeof(log_data.cart.length)=='undefined') {
        if(log_data.cart.checked) log_srl[log_srl.length] = log_data.cart.value;
    } else {
        var length = log_data.cart.length;
        for(var i=0; i<length; i++) {
            if(log_data.cart[i].checked) log_srl[log_srl.length] = log_data.cart[i].value;
        }
    }
	
	//로그 선택하지 않았을때 오류메세지 출력
    if(log_srl.length < 1) { alert('선택 대상이 없습니다'); return; }
	//삭제 취소시 리턴
    if(!confirm('선택한 로그를 삭제합니다.')) return;

	//act에 넘겨줄 배열생성
    var params = new Array();
    params['log_srls'] = log_srl.join('@'); //값을 하나로 합침
    exec_xml('gradeup','procGradeupAdminConfirmLogDelete', params, completeDeletelog); //모듈이름//액션이름//보내줄값//콜백함수//콜백함수에서 받을변수(미입력시 message 기본내장)
}

// 전체로그삭제
function jsDeleteGradeConfirmLogAll() {
    if(!confirm('전체로그를 삭제합니다.\n삭제후 데이터 복구는 불가능합니다.')) return;
    exec_xml('gradeup','procGradeupAdminConfirmLogDeleteAll', {}, completeDeletelog); //모듈이름//액션이름//보내줄값//콜백함수//콜백함수에서 받을변수(미입력시 message 기본내장)
}


//////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////


/* 일괄 삭제 후 */
function completeDeletelog(ret_obj) {
    alert(ret_obj['message']);
    location.reload();
}