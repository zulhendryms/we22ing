
<style>
    .w-100 {
        width: 100%!important;
    }
    .w-50 {
        width: 50%!important;
    }
    .w-25 {
        width: 25% !important;
    }
    .w-75 {
        width: 75% !important;
    }
    header {
        padding: 2px 5px;
        display: flex;
        border: 1px solid black;
        margin-bottom: 5px;
        height : 200px;
    }
    header > div:nth-child(odd) {
        border-right: 1px solid black;
    }
    header > div.w-50 {
        padding: 2px 5px;
    }
</style>

<style>
    img {
        display: block;
        margin-left: auto;
        margin-right: auto;
    }
    @page { margin: 110px 25px; }
    p { page-break-after: always; }
    p:last-child { page-break-after: never; }

    .table table {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        margin-bottom: 20px;
    }
    
    .table table th {
        padding: 15px 10px;
        color: #5D6975;
        border-bottom: 1px solid #C1CED9;
        white-space: nowrap;
        font-weight: bold; 
        color: #ffffff;
        border-top: 1px solid  #5D6975;
        border-bottom: 1px solid  #5D6975;
        background: #888888;
        font-size: 14px;
        padding-top:15px;
        padding-bottom:15px;
        padding-left:10px;
        padding-right:10px;
    }
    .table table td {
        border: 1px solid #dddddd;
        vertical-align: top;
        font-size: 10px;
        padding-top:10px;
        padding-bottom:2px;
        padding-left:2px;
        padding-right:1px;
    }
    .table table td.firstcol { padding-left: 5px; }
    .table table td.lascol { padding-right: 5px; }
    .table table th.firstcol { padding-left: 5px; }
    .table table th.lascol { padding-right: 5px; }
    .table table td.group {
        padding-left: 8px;
        padding-top:8px;
        font-size: 12px;
        padding-bottom:8px;
        background: #F5F5F1; 
        font-weight: bold;
    }
</style>