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
        $table .= "<tr><th>时间</th><th>命令</th><th>状态</th><th>操作</th></tr>";
        $server = '';
        foreach($data as $row)
        {
            $rowtmp = explode("|", $row);
            if(count($rowtmp) > 1)
            {
                $table .= "<tr><td>{$rowtmp[0]}</td><td>{$rowtmp[1]}</td><td>{$rowtmp[2]}</td><td><a href='/?op=start&agent={$server}&id={$rowtmp[3]}'>开始运行</a> | <a href='/?op=stop&agent={$server}&id={$rowtmp[3]}'>停止</a></td></tr>";
            }else{
                $server = $row;
                $table .= "<tr><td colspan='4'>{$row}</td></tr>";
            }
        }
        $table .= "</table>";
        return $table;
    }

}