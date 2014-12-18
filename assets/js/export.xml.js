(function ($) {
    $.extend($.jgrid, {
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
            link.href =  URL.createObjectURL(blob);;
            link.style = "visibility:hidden";
            link.download = fileName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    });
})(jQuery);
