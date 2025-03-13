$(document).ready(function() {
    // 전역 변수
    let selectedReports = [];
    var currentReportIndex = 0;

    // PDF.js 라이브러리 로드 확인
    if (window.pdfjsLib) {
        pdfjsLib.GlobalWorkerOptions.workerSrc = '/uzart/www/js/pdf.worker.min.js';
    }

    // Client 목록 불러오기
    $.getJSON("/uzart/www/api/reports_api.php?action=load_clients", function(data) {
        if (data.error) {
            alert("클라이언트 데이터를 불러오는 중 오류 발생: " + data.error);
            return;
        }
        $.each(data.clients, function(i, client) {
            $("#client").append(new Option(client, client));
        });
    }).fail(function() {
        alert("클라이언트 데이터를 불러오는 중 오류가 발생했습니다.");
    });

    // 보고서 데이터 조회
    $("#searchReport").off('click').on('click', function() {
        let startDate = $("#start_date").val();
        let endDate = $("#end_date").val();
        let client = $("#client").val();
        let server = $("#server").val();

        if (!client) {
            alert("클라이언트를 선택하세요.");
            return;
        }

        let today = new Date().toISOString().split('T')[0];
        if (!startDate && !endDate) {
            startDate = today;
            endDate = today;
        } else if (!startDate) {
            startDate = today;
        } else if (!endDate) {
            endDate = today;
        }

        $.getJSON("/uzart/www/api/reports_api.php?action=load_report", {
            start_date: startDate,
            end_date: endDate,
            client: client,
            server: server
        })
        .done(function(data) {
            let tableBody = $("#reportResults");
            tableBody.empty();
            if (data.error) {
                alert("데이터 불러오기 오류: " + data.error);
                return;
            }
            if (data.length === 0) {
                tableBody.append('<tr><td colspan="5">조회된 데이터가 없습니다.</td></tr>');
            } else {
                $.each(data, function(i, row) {
                    tableBody.append(`
                        <tr>
                            <td>
                                <input type="checkbox" name="reportSelect[]" value="${row.id}"
                                data-client="${row.client}"
                                data-server="${row.server_name}"
                                data-created="${row.created_at}"
                                data-check="${row.check_status}">
                            </td>
                            <td>${row.server_name}</td>
                            <td>${row.client}</td>
                            <td>${row.created_at}</td>
                            <td>${row.check_status}</td>
                        </tr>
                    `);
                });
                $("input[name='reportSelect[]']").off('change').on('change', function() {
                    var anySelected = $("input[name='reportSelect[]']:checked").length > 0;
                    $("#checkReport, #viewReport, #downloadReport").prop("disabled", !anySelected);
                });
            }
        })
        .fail(function(jqXHR, textStatus) {
            alert("보고서 데이터를 불러오는 중 오류 발생: " + textStatus);
        });
    });

    // 전체 선택 버튼
    $("#selectAll").off('click').on('click', function(e) {
        e.preventDefault();
        if ($("input[name='reportSelect[]']").length === $("input[name='reportSelect[]']:checked").length) {
            $("input[name='reportSelect[]']").prop("checked", false);
        } else {
            $("input[name='reportSelect[]']").prop("checked", true);
        }
        var anySelected = $("input[name='reportSelect[]']:checked").length > 0;
        $("#checkReport, #viewReport, #downloadReport").prop("disabled", !anySelected);
    });

    // 선택된 보고서 ID들을 콤마로 연결
    function getSelectedIds() {
        var ids = $("input[name='reportSelect[]']:checked").map(function() {
            return $(this).val();
        }).get();
        return ids.join(",");
    }

    // "점검" 버튼 클릭 시 PDF 모달 + 점검 입력
    $("#checkReport").off('click').on('click', function() {
        var combinedIds = getSelectedIds();
        if (!combinedIds) {
            alert("보고서를 선택해주세요.");
            return;
        }

        var pdfUrl = "/uzart/www/api/view_report.php?download=2&ids=" + combinedIds;
        pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf) {
            pdfDoc = pdf;
            totalPages = pdf.numPages;
            currentPage = 1;

            function renderPage(pageNum) {
                pdfDoc.getPage(pageNum).then(function(page) {
                    let viewport = page.getViewport({ scale: 1.3 });
                    let canvas = document.createElement("canvas");
                    let context = canvas.getContext("2d");
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;
                    page.render({ canvasContext: context, viewport: viewport }).promise.then(function() {
                        $("#pdfViewer").empty().append(canvas);
                        $("#pageInfo").text(pageNum + " / " + totalPages);
                        updatePageControls();

                        // 마지막 페이지면 점검 폼 추가
                        if (pageNum === totalPages) {
                            let checkForm = `
                                <div id="checkForm" style="margin-top: 20px;">
                                    <h3>점검 내역 입력</h3>
                                    <input type="text" id="net_err" placeholder="네트워크 오류" value="이상 없음"><br>
                                    <input type="text" id="tmp_log" placeholder="TMP 로그" value="이상 없음"><br>
                                    <input type="text" id="web_log" placeholder="WEB 로그" value="해당사항 없음"><br>
                                    <input type="text" id="was_log" placeholder="WAS 로그" value="해당사항 없음"><br>
                                    <input type="text" id="db_log" placeholder="DB 로그" value="해당사항 없음"><br>
                                    <input type="text" id="sys_log" placeholder="시스템 로그" value="이상 없음"><br>
                                    <textarea id="comments" placeholder="코멘트">- 특이사항 없음</textarea><br>
                                    <button id="saveCheckReport">저장</button>
                                </div>`;
                            $("#pdfViewer").append(checkForm);

                            $("#saveCheckReport").off('click').on('click', function() {
                                $.ajax({
                                    url: "/uzart/www/api/add_check.php",
                                    method: "POST",
                                    data: {
                                        ids: combinedIds,
                                        check_status: "true",
                                        net_err: $("#net_err").val(),
                                        tmp_log: $("#tmp_log").val(),
                                        web_log: $("#web_log").val(),
                                        was_log: $("#was_log").val(),
                                        db_log: $("#db_log").val(),
                                        sys_log: $("#sys_log").val(),
                                        comments: $("#comments").val()
                                    },
                                    dataType: "json",
                                    success: function(response) {
                                        if (response.success) {
                                            showAlert("✅ 점검이 저장되었습니다.", "success");
                                            $("#pdfModal").fadeOut();
                                        } else {
                                            showAlert("❌ 저장 오류: " + response.error, "error");
                                        }
                                    },
                                    error: function() {
                                        alert("저장 중 오류 발생");
                                    }
                                });
                            });
                        }
                    });
                });
            }

            renderPage(currentPage);
            $("#pdfModal").fadeIn();

            $("#nextPage").off('click').on('click', function(e) {
                e.preventDefault();
                if (currentPage < totalPages) {
                    currentPage++;
                    renderPage(currentPage);
                }
            });
            $("#prevPage").off('click').on('click', function(e) {
                e.preventDefault();
                if (currentPage > 1) {
                    currentPage--;
                    renderPage(currentPage);
                }
            });
        }).catch(function(error) {
            alert("PDF 로드 오류: " + error);
        });
    });

    // 점검 모달 닫기
    $(".cancel-btn").off('click').on('click', function() {
        $("#checkReportModal").fadeOut();
    });
    $(window).off('click').on('click', function(event) {
        if ($(event.target).is("#checkReportModal")) {
            $("#checkReportModal").fadeOut();
        }
    });

    // 알람 폼
    function showAlert(message, type) {
        let alertBox = $("#alertMessage");
        alertBox.text(message).removeClass("success error").addClass(type).fadeIn();
        setTimeout(function() {
            alertBox.fadeOut();
        }, 3000);
    }

    // "보고서" 버튼 (미리보기)
    $("#viewReport").off('click').on('click', function() {
        var combinedIds = getSelectedIds();
        if (!combinedIds) {
            alert("보고서를 선택해주세요.");
            return;
        }
        var pdfUrl = "/uzart/www/api/view_report.php?download=2&ids=" + combinedIds;
        pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf) {
            pdfDoc = pdf;
            totalPages = pdf.numPages;
            currentPage = 1;
            renderPage(currentPage);
            updatePageControls();
            $("#modalDownloadReport").attr("href", "/uzart/www/api/view_report.php?download=1&ids=" + combinedIds);
            $("#pdfModal").fadeIn();
        }).catch(function(error) {
            alert("PDF 로드 오류");
        });
    });

    // "다운로드" 버튼
    $("#downloadReport").off('click').on('click', function() {
        var combinedIds = getSelectedIds();
        if (!combinedIds) {
            alert("다운로드할 보고서를 선택해주세요.");
            return;
        }
        window.location.href = '/uzart/www/api/view_report.php?download=1&ids=' + combinedIds;
    });

    // PDF.js 관련 함수
    let scale = 1.3, pdfDoc, currentPage, totalPages;
    function renderPage(pageNumber) {
        pdfDoc.getPage(pageNumber).then(function(page) {
            let viewport = page.getViewport({ scale: scale });
            let canvas = document.createElement("canvas");
            let context = canvas.getContext("2d");
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            let renderContext = { canvasContext: context, viewport: viewport };
            page.render(renderContext).promise.then(function() {
                $("#pdfViewer").empty().append(canvas);
                $("#pageInfo").text(pageNumber + " / " + totalPages);
                updatePageControls();
            });
        });
    }
    function updatePageControls() {
        $("#prevPage").prop("disabled", currentPage <= 1);
        $("#nextPage").prop("disabled", currentPage >= totalPages);
    }
    $("#prevPage").off('click').on('click', function(e) {
        e.preventDefault();
        if (currentPage > 1) {
            currentPage--;
            renderPage(currentPage);
            updatePageControls();
        }
    });
    $("#nextPage").off('click').on('click', function(e) {
        e.preventDefault();
        if (currentPage < totalPages) {
            currentPage++;
            renderPage(currentPage);
            updatePageControls();
        }
    });

    // 모달 닫기 (PDF 미리보기 모달)
    $(".close-btn").off('click').on('click', function() {
        setTimeout(() => {
            $("input[name='reportSelect[]']:checked").each(function() {
                var reportId = $(this).val();
                console.log("deletePdfFile 호출됨", reportId);
                deletePdfFile(reportId);
            });
        }, 5000);
        $("#pdfModal").fadeOut();
    });
    $(window).off('click').on('click', function(event) {
        if ($(event.target).is("#pdfModal")) {
            setTimeout(() => {
                $("input[name='reportSelect[]']:checked").each(function() {
                    var reportId = $(this).val();
                    console.log("deletePdfFile 호출됨", reportId);
                    deletePdfFile(reportId);
                });
            }, 5000);
            $("#pdfModal").fadeOut();
        }
    });

    // PDF 삭제 요청 함수
    function deletePdfFile(reportId) {
        $.ajax({
            url: "/uzart/www/api/view_report.php",
            method: "GET",
            data: { download: "3", id: reportId },
            success: function(response) {
                console.log("✅ PDF 삭제 완료:", response);
            },
            error: function() {
                console.log("❌ PDF 삭제 실패");
            }
        });
    }
});
