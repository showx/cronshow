<?php
/**
 * Config配置获取
 * Author:show
 */

namespace Application\Library;

Class WebView
{
    /**
     * 列出表格
     *
     * @param [type] $data
     * @return void
     */
    public static function listTable($data)
    {
        $table = "<table border='1'>";
        $table .= "<tr><th>时间</th><th>命令</th></tr>";
        foreach($data as $row)
        {
            $rowtmp = explode("|", $row);
            if(count($rowtmp) > 1)
            {
                $table .= "<tr><td>{$rowtmp[0]}</td><td>{$rowtmp[1]}</td></tr>";
            }else{
                $table .= "<tr><td colspan='2'>{$row}</td></tr>";
            }
        }
        $table .= "</table>";
        return $table;
    }

    /**
     * 列出表格
     *
     * @param [type] $data
     * @return void
     */
    public static function statusTable($data)
    {
        $table = "<table border='1'>";
        $table .= "<tr><th>时间</th><th>命令</th><th>状态</th></tr>";
        foreach($data as $row)
        {
            $rowtmp = explode("|", $row);
            if(count($rowtmp) > 1)
            {
                $table .= "<tr><td>{$rowtmp[0]}</td><td>{$rowtmp[1]}</td><td>{$rowtmp[2]}</td></tr>";
            }else{
                $table .= "<tr><td colspan='3'>{$row}</td></tr>";
            }
        }
        $table .= "</table>";
        return $table;
    }

}