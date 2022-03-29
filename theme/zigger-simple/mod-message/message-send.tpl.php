<div class="tit">
	<h2>새로운 메시지 발송</h2>
	<a href="#" class="close"><i class="fa fa-times"></i></a>
</div>

<div class="cont">

    <?php if ($is_mbinfo_show) { ?>
    <form action="<?php $this->form(); ?>">
        <input type="hidden" name="reply_parent_idx" value="<?php echo $reply_parent_idx; ?>" />
    	<table class="table_wrt">
    		<colgroup>
    			<col style="width: 120px;" />
    			<col style="width: auto;" />
    		</colgroup>
    		<tbody>
    			<tr>
    				<th>받는회원</th>
    				<td><input type="text" name="to_mb_id" placeholder="아이디 입력" class="inp" title="받는 회원" value="<?php echo $to_mb_id; ?>" /></td>
    			</tr>
    			<tr>
    				<th>내용</th>
    				<td>
                        <textarea name="article" title="메시지 내용"></textarea>
                        <span class="tbltxt">5글자 이상 입력해주세요.</span>
                    </td>
    			</tr>
    		</tbody>
    	</table>

        <div class="btn mt10">
            <button type="submit" class="btn1">발송</button>
        </div>
    </form>
    <?php } ?>

    <?php if (!$is_mbinfo_show) { ?>
    <p class="sment">메시지를 발송할 수 있는 권한이 없습니다.</p>
    <?php } ?>

</div>
