<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><👤고객 관리></title>
    <link rel="stylesheet" href="/uzart/www/css/style.css">
</head>

<body>
    <h1>👤 고객 관리</h1>
      <div class="filter-container">
        <form id="userFilterForm" class="filter-form">

            <label>Client:
                <input type="text" name="client" id="client" placeholder="클라이언트 이름">
            </label>
            <label>Server Name:
                <input type="text" name="server_name" id="server_name" placeholder="서버 이름">
            </label>

    	    <label>표시 개수:
 	        <select name="limit" id="limit">
	            <option value="10">10개</option>
	            <option value="20" selected>20개</option>
	            <option value="30">30개</option>
	            <option value="40">40개</option>
	            <option value="50">50개</option>
	        </select>
	    </label>

            <button type="submit" class="search-button">조회</button>
        </form>

      <div class="add-client-button-container">
        <!-- 고객 추가 버튼 -->
       <button id="addClientBtn" class="add-client-btn"> 고객 추가</button>
      </div>
 

      </div>


    <!-- 로그 테이블 -->
    <table id="userTable">
        <thead>
            <tr>
                <th>클라이언트 이름</th>
                <th>서버 이름</th>
                <th>API Key</th>
		<th class="down-col">Agent Down</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="4" style="text-align: center;">조회된 고객이 없습니다.</td>
            </tr>
        </tbody>
    </table>

    <!-- 페이지네이션 UI -->
    <div id="pagination"></div>

   <!-- 고객 추가 모달 -->
    <div id="addClientModal" class="modal">
        <div class="modal-content">
            <h3>🆕 고객 추가</h3>
            <label for="newClient">Client:</label>
            <input type="text" id="newClient" placeholder="클라이언트 이름">

            <label for="newServer">Server:</label>
            <input type="text" id="newServerName" placeholder="서버 이름">

            <div class="modal-buttons">
                <button id="cancelModal">취소</button>
                <button id="confirmAddClient">확인</button>
            </div>
        </div>
    </div>
    <!-- 알림 메세지 UI 추가 -->
    <div id="alertMessage" class="alert-message"></div>

    <!-- jQuery 라이브러리 -->
    <!-- <script src="/uzart/www/js/jquery-3.6.0.min.js"></script> -->
    <!-- URL 변경 -->
    <!-- <script src="/uzart/www/js/userpage.js"></script> -->

</body>
</html>
