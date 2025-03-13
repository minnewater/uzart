$(document).ready(function () {
    function loadUsers(page = 1) {
        let formData = {
            client: $("#client").val(),
            server_name: $("#server_name").val(),
            limit: $("#limit").val(),
            page: page
        };

        $.ajax({
            url: "/uzart/www/api/users_api.php",
            type: "GET",
            data: formData,
            dataType: "json",
            success: function (response) {
                let tableBody = $("#userTable tbody");
                let paginationDiv = $("#pagination");
                tableBody.empty(); // 기존 데이터 삭제
                paginationDiv.empty(); // 기존 페이지네이션 삭제

                if (response.users.length > 0) {
                    $.each(response.users, function (index, user) {
			let blurredKey = "•".repeat(user.api_key.length); // API Key 블러 처리
                        tableBody.append(`
                            <tr>
                                <td>${user.client}</td>
                                <td>${user.server_name}</td>
                                <td>
				    <span class="api-key blurred" data-key="${user.api_key}">${blurredKey}</span>
				</td>
                                <td>
                                    <button class="download-btn" data-client="${user.client}" data-server="${user.server_name}">📥</button>
                                </td>
                            </tr>
                        `);
                    });

                    // 페이지네이션 생성
                    for (let i = 1; i <= response.total_pages; i++) {
                        let activeClass = (i === response.current_page) ? 'active' : '';
                        paginationDiv.append(`<button class="page-btn ${activeClass}" data-page="${i}">${i}</button>`);
                    }
                } else {
                    tableBody.append('<tr><td colspan="4" style="text-align: center;">조회된 고객이 없습니다.</td></tr>');
                }
            },
            error: function () {
                alert("고객 데이터를 불러오는 중 오류가 발생했습니다.");
            }
        });
    }

    // ✅ 다운로드 버튼 클릭 이벤트 추가
    $(document).on("click", ".download-btn", function () {
        let client = $(this).data("client");
        let server = $(this).data("server");

    // ✅ 기존 폼이 있으면 삭제 후 생성
    $("#downloadForm").remove();

    let form = $("<form>")
        .attr("id", "downloadForm")
        .attr("method", "GET")
        .attr("action", "/uzart/www/api/download_api_key.php");

    form.append($("<input>").attr("type", "hidden").attr("name", "client").val(client));
    form.append($("<input>").attr("type", "hidden").attr("name", "server").val(server));

    $("body").append(form);
    form.submit();
});

//        let downloadUrl = `/uzart/www/api/download_api_key.php?client=${client}&server=${server}`;
//        window.location.href = downloadUrl;
//    });

    // 조회 버튼 클릭 시
    $("#userFilterForm").submit(function (event) {
        event.preventDefault();
        loadUsers();
    });

    // 페이지네이션 버튼 클릭 시
    $(document).on("click", ".page-btn", function () {
        let page = $(this).data("page");
        loadUsers(page);
    });


    // API Key 클릭 시 블러 처리 토글
    $(document).on("click", ".api-key", function () {
        let keyElement = $(this);
        let originalKey = keyElement.data("key");

        if (keyElement.hasClass("blurred")) {
            keyElement.text(originalKey).removeClass("blurred");
        } else {
            let blurredKey = "•".repeat(originalKey.length);
            keyElement.text(blurredKey).addClass("blurred");
        }
    });

    // 고객 추가 모달 열기
    $("#addClientBtn").click(function () {
        $("#addClientModal").fadeIn();
    });

    // 모달 닫기 (취소 버튼)
    $("#cancelModal").click(function () {
        $("#addClientModal").fadeOut();
    });

    // 고객 추가 (확인 버튼)
    $("#confirmAddClient").click(function () {
        let newClient = $("#newClient").val().trim();
        let newServerName = $("#newServerName").val().trim();

        if (newClient === "" || newServerName === "") {
            showAlert("⚠️ 클라이언트와 서버 이름을 입력하세요.", "error");
            return;
        }

        $.ajax({
            url: "/uzart/www/api/add_client.php",
            type: "POST",
            data: { client: newClient, server_name: newServerName },
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    showAlert("✅ 고객이 추가되었습니다.", "success");
                    $("#addClientModal").hide();
                    loadUsers();
                } else {
                    showAlert("❌ 오류 발생: " + response.error, "error");
                }
            }
        });
    });

    function showAlert(message, type) {
        let alertBox = $("#alertMessage");
        alertBox.text(message).removeClass("success error").addClass(type).fadeIn();

        setTimeout(function () {
            alertBox.fadeOut();
        }, 3000);
    }

    // 페이지 로드 시 기본값 조회
    loadUsers();
});
