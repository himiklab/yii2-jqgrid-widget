jQuery.extend(jQuery.jgrid, {
    XMLExport: function (widgetId, fileName) {
        var xmlContent = "<?xml version='1.0'?>\r\n"
            + "<?mso-application progid='Excel.Sheet'?>\r\n"
            + "<Workbook xmlns='urn:schemas-microsoft-com:office:spreadsheet' xmlns:o='urn:schemas-microsoft-com:office:office' " +
            "xmlns:x='urn:schemas-microsoft-com:office:excel' xmlns:ss='urn:schemas-microsoft-com:office:spreadsheet' " +
            "xmlns:html='http://www.w3.org/TR/REC-html40'>\r\n"
            + "<Worksheet ss:Name='WorksheetName'>\r\n"
            + "<Table>\r\n";

        xmlContent += "<Row>\r\n";
        $("#gview_jqGrid-" + widgetId + " table tr[class='ui-jqgrid-labels'] th:visible div").each(function () {
            xmlContent += "<Cell><Data ss:Type='String'>" + jQuery(this).text() + "</Data></Cell>\r\n";
        });
        xmlContent += "</Row>\r\n";

        $("#jqGrid-" + widgetId + " tr[class!='jqgfirstrow']").each(function () {
            xmlContent += "<Row>\r\n";
            $("td:visible", this).each(function () {
                xmlContent += "<Cell><Data ss:Type='String'>" + jQuery(this).text() + "</Data></Cell>\r\n";
            });
            xmlContent += "</Row>\r\n";
        });

        xmlContent += "</Table>\r\n"
            + "</Worksheet>\r\n"
            + "</Workbook>";

        var link = document.createElement("a");
        var blob = new Blob([xmlContent]);
        link.href = URL.createObjectURL(blob);
        link.style = "visibility:hidden";
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
});

var getJqGridState = function (grid) {
    var colModel = grid.jqGrid("getGridParam", "colModel");
    var gridParams = {};
    gridParams.colStatesVisible = {};

    var position = 0;
    for (var i = 0; i < colModel.length; ++i) {
        if (!colModel[i].hidden) {
            gridParams.colStatesVisible[colModel[i].name] = position++;
        }
    }

    gridParams.search = grid.jqGrid("getGridParam", "search");
    gridParams.postDataFilters = grid.jqGrid("getGridParam", "postData").filters;

    return gridParams;
};

var setJqGridState = function (gridParams, grid, reload) {
    if (gridParams.colStatesVisible) {
        var colModel = grid.jqGrid("getGridParam", "colModel");
        for (var i = 0; i < colModel.length; ++i) {
            var cmName = colModel[i].name;
            if (cmName !== "rn" && cmName !== "cb" && cmName !== "subgrid") {
                if (colModel[i].hidden === true && gridParams.colStatesVisible[cmName] !== undefined) {
                    grid.showCol(cmName);
                } else if (colModel[i].hidden === false && gridParams.colStatesVisible[cmName] === undefined) {
                    grid.hideCol(cmName);
                }
            }
        }

        var iColByName = grid.jqGrid("getGridParam", "iColByName");
        var fixedOrder = $.map(Object.keys(gridParams.colStatesVisible), function (name) {
            return (iColByName[name] === undefined) ? null : name;
        });
        for (i = 0; i < colModel.length; ++i) {
            cmName = colModel[i].name;
            if (fixedOrder.indexOf(cmName) === -1) {
                fixedOrder.push(cmName);
            }
        }

        if (fixedOrder.indexOf("cb") !== -1) {
            fixedOrder.splice(fixedOrder.indexOf("cb"), 1);
        }
        if (fixedOrder.indexOf("rn") !== -1) {
            fixedOrder.splice(fixedOrder.indexOf("rn"), 1);
        }
        fixedOrder.unshift("cb");
        fixedOrder.unshift("rn");

        grid.jqGrid("remapColumnsByName", fixedOrder, true);
    }

    if (gridParams.search) {
        grid.jqGrid("setGridParam", {search: gridParams.search});
    }
    if (gridParams.postDataFilters) {
        var newPostData = grid.jqGrid("getGridParam", "postData");
        newPostData.filters = gridParams.postDataFilters;
        grid.jqGrid("setGridParam", {postData: newPostData});
    }

    $("input[id*='gs_'], select[id*='gs_']").val("");
    if (reload) {
        grid.trigger("reloadGrid", [{page: 1}]);
    }
};
