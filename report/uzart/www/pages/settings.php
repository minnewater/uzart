<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>⚙️ 설정</title>
    <link rel="stylesheet" href="/uzart/www/css/style.css">
</head>
<body>
    <div class="content">
        <h1>⚙️ 설정</h1>
        <div class="filter-container">
            <form id="settingsForm" class="filter-form">
                <div class="form-left">
                    <h3>사용자 관리</h3>
                </div>
                <div class="form-right">
                    <button type="button" id="viewUserBtn" class="search-button">사용자 조회</button>
                    <button type="button" id="addUserBtn" class="action-button">사용자 추가</button>
                </div>
            </form>
        </div>

        <table id="userTable">
            <thead>
                <tr>
                    <th>아이디</th>
                    <th>이름</th>
                    <th>직급</th>
                    <th>내선번호</th>
                    <th>핸드폰 번호</th>
                    <th>이메일</th>
                    <th>그룹</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="7" style="text-align: center;">조회된 사용자가 없습니다.</td>
                </tr>
            </tbody>
        </table>

        <div id="pagination"></div>

        <div id="addUserModal" class="modal">
            <div class="modal-content">
                <h3>🆕 사용자 추가</h3>
                <label for="newUsername">아이디:</label>
                <input type="text" id="newUsername" placeholder="아이디">
                <label for="newPassword">패스워드:</label>
                <input type="password" id="newPassword" placeholder="패스워드">
                <label for="newGroupId">사용자 그룹:</label>
                <select id="newGroupId" name="group_id"></select>
                <label for="newName">이름:</label>
                <input type="text" id="newName" placeholder="이름">
                <label for="newPosition">직급:</label>
                <input type="text" id="newPosition" placeholder="직급">
                <label for="newOffice">내선번호:</label>
                <input type="text" id="newOffice" placeholder="0000" maxlength="4">
                <label for="newPhone">핸드폰 번호:</label>
                <input type="text" id="newPhone" placeholder="000-0000-0000">
                <label for="newEmail">이메일:</label>
                <input type="email" id="newEmail" placeholder="이메일">
                <div class="modal-buttons">
                    <button id="confirmAddUser">확인</button>
                    <button id="cancelModal">취소</button>
                </div>
            </div>
        </div>

        <div id="alertMessage" class="alert-message"></div>
    </div>
</body>
</html>
