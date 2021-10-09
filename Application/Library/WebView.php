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
        $table .= "<tr>
        <th>ID</th>
        <th>时间</th>
        <th>命令</th>
        <th>状态</th>
        <th>简略结果</th>
        <th>操作</th>
        </tr>";
        $server = '';
        foreach($data as $row)
        {
            $rowtmp = explode("|", $row);
            if(count($rowtmp) > 1)
            {
                $rowtmp[4] = substr($rowtmp[4], 0, 30)."..";
                $id = md5($rowtmp[3]);
                $table .= "<tr>
                <td>{$id}</td>
                <td>{$rowtmp[0]}</td>
                <td>{$rowtmp[1]}</td>
                <td>{$rowtmp[2]}</td>
                <td>{$rowtmp[4]}</td>
                <td>
                <a href='/?op=Master_result&agent={$server}&id={$rowtmp[3]}'>详细</a> | 
                <a href='/?op=Master_start&agent={$server}&id={$rowtmp[3]}'>运行</a> | 
                <a href='/?op=Master_stop&agent={$server}&id={$rowtmp[3]}'>停止</a>
                </td>
                </tr>";
            }else{
                $server = $row;
                $table .= "<tr><td colspan='6'>{$row}</td></tr>";
            }
        }
        $table .= "</table>";
        return $table;
    }

}