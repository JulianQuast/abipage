<?php

/*
 * Copyright (C) 2012 Johannes Bechberger
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

function tpl_news_list($news, $as_page = true) {
    if ($as_page) {
        tpl_before("news-list");
    }
    if (Auth::canWriteNews()){
        tpl_infobox('Neue Nachrichten', ' können <a href="' . tpl_url("news/write") . '">hier</a> geschrieben werden.');
    }
    foreach($news as $item){
        tpl_news($item);
    }
    if ($as_page) {
        tpl_after();
    }
}

function tpl_news($news_item){
    tpl_item_before($news_item["title"], "newspaper", "news-item");
    echo formatText($news_item["content"]);
    tpl_item_after_news_item($news_item);
}

function tpl_item_after_news_item($news_item) {
    ?>
    </div>
    <hr/>
    <div class="item-footer">
        <ul>
            <li class="time_span_li" style="width: 50%"><?php tpl_time_span($news_item["time"]) ?></li>
            <li class="user_span_li" style="width: 50%; text-align: right"><?php tpl_user_span($news_item["userid"]) ?></li>
        </ul>
    </div>
    </div>
    <?
}

function tpl_write_news($as_page = true) {
    if ($as_page) {
        tpl_before("write_news");
    }
    tpl_item_before_form(array("action" => "POST", "method" => tpl_url("news")), "Nachricht schreiben", "pencil", "write-news");
    ?>
    <input type="text" name="title" placeholde="Titel">
    <texarea name="text" placeholder="Text" data-markdown-preview="news-preview"></texarea>
    <h4>Vorschau</h4>
    <div class="preview" id="news-preview"></div>
    <?php
    tpl_item_after_form(array("write" => array("text" => "Schreiben"), "write-email" => array("text" => "Schreiben und als Newsletter versenden", "icon" => "email")));
    if ($as_page) {
        tpl_after();
    }
}