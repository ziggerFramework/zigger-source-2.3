<!-- top source code -->
<?php if ($is_tf_source_show) { ?>
<div id="mod_board_top_source"><?php echo $top_source; ?></div>
<?php } ?>

<form <?php echo $this->form(); ?>>
	<input type="hidden" name="category" value="<?php echo $category; ?>" />
	<input type="hidden" name="page" value="<?php echo $page; ?>" />
	<input type="hidden" name="where" value="<?php echo $where; ?>" />
	<input type="hidden" name="keyword" value="<?php echo $keyword; ?>" />
	<input type="hidden" name="board_id" value="<?php echo $board_id; ?>" />
	<input type="hidden" name="thisuri" value="<?php echo $thisuri; ?>" />

	<!-- total count -->
	<p id="boatd-total">전체 <strong><?php echo $total_cnt; ?></strong>개의 갤러리</p>

	<!-- category -->
	<?php if ($is_category_show) { ?>
	<div id="board-cat">
        <h3 class="sound_only_ele">게시판 카테고리</h3>
		<?php echo $print_category; ?>
	</div>
	<?php } ?>

	<!-- list -->
	<div id="board-list">

		<?php if ($is_ctr_show) { ?>
		<label><input type="checkbox" class="cnum_allchk" alt="게시글 전체 선택" /> 게시글 전체 선택</label>
		<?php } ?>

        <!-- 공지글 -->
		<?php if (count($print_notice)>0) { ?>
		<table class="notice mt15">
            <caption>게시판 리스트</caption>
			<colgroup>
				<?php if ($is_ctr_show) { ?>
				<col scope="col" style="width: 50px;" />
				<?php } ?>
				<col scope="col" style="width: 60px;" />
				<col scope="col" style="width: auto;" />
				<col scope="col" style="width: 120px;" />
				<col scope="col" style="width: 60px;" />
				<col scope="col" style="width: 50px;" />
				<?php if ($is_likes_show) { ?>
				<col scope="col" style="width: 70px;" />
				<?php } ?>
			</colgroup>

			<tbody>
				<?php foreach ($print_notice as $list) { ?>
				<tr>
					<?php if ($is_ctr_show) { ?>
					<td class="chk"><input type="checkbox" name="cnum[]" value="<?php echo $list['idx']; ?>" /></td>
					<?php } ?>
					<td class="no"><strong>공지</strong></td>
					<td class="sbj">

						<a href="<?php echo $list[0]['get_link']; ?>" class="sbj">
							<?php echo $list[0]['secret_ico']; ?>
							<?php echo $list[0]['subject']; ?>

    						<?php echo $list[0]['file_ico']; ?>
    						<?php echo $list[0]['new_ico']; ?>
    						<?php echo $list[0]['hot_ico']; ?>

    						<?php if ($is_comment_show) { ?>
    						<span class="cmt"><?php echo $list[0]['comment_cnt']; ?></span>
    						<?php } ?>
						</a>

					</td>
					<td class="writer">
                        <?php if ($list[0]['profileimg']) { ?>
                        <div class="board-mb-profileimg" style="background-image: url('<?php echo $list[0]['profileimg']; ?>');"></div>
                        <?php } ?>

                        <?php echo $list[0]['writer']; ?>
                    </td>
					<td class="no" title="<?php echo $list['datetime']; ?>"><?php echo $list['date']; ?></td>
					<td class="no"><?php echo $list['view']; ?></td>
					<?php if ($is_likes_show) { ?>
        	        <td class="no"><strong><?php echo $list['likes_cnt']; ?></strong>/<?php echo $list['unlikes_cnt']; ?></td>
        	        <?php } ?>

                    <!-- for mobile -->
                    <td class="mobile-info">
                        <ul>
                            <li><strong>작성자</strong><?php echo $list[0]['writer']; ?></li>
                            <li><strong>날짜</strong><?php echo $list['date']; ?></li>
                            <li><strong>조회</strong><?php echo $list['view']; ?></li>
                            <li><strong>좋아요</strong><?php echo $list['likes_cnt']; ?>/<?php echo $list['unlikes_cnt']; ?></li>
                        </ul>
                    </td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		<?php } ?>

		<!-- 갤러리 -->
		<ul class="list">
			<?php foreach($print_arr as $list) { ?>
			<li>
				<div class="tmb" <?php if ($list[0]['thumbnail']) { ?>style="background-image: url('<?php echo $list[0]['thumbnail']; ?>');"<?php } ?>>
					<a href="<?php echo $list[0]['get_link']; ?>" class="link"></a>
				</div>
				<div class="info">

					<span class="tit">
						<a href="<?php echo $list[0]['get_link']; ?>" class="sbj">
							<?php if ($is_ctr_show) { ?>
							<input type="checkbox" name="cnum[]" value="<?php echo $list['idx']; ?>" />
							<?php } ?>
							<?php echo $list[0]['secret_ico']; ?>
							<?php echo $list[0]['subject']; ?>
						</a>
                        <?php echo $list[0]['new_ico']; ?>
                        <?php echo $list[0]['hot_ico']; ?>


						<?php if ($is_comment_show) { ?>
						<span class="cmt"><?php echo $list[0]['comment_cnt']; ?></span>
						<?php } ?>
					</span>
                    <span class="txt"><?php echo $list[0]['article']; ?></span>

					<span class="date" title="<?php echo $list['datetime']?>"><?php echo $list['date']; ?></span>
					<span class="writer">
                        <?php if ($list[0]['profileimg']) { ?>
                        <div class="board-mb-profileimg" style="background-image: url('<?php echo $list[0]['profileimg']; ?>');"></div>
                        <?php } ?>

                        <?php echo $list[0]['writer']; ?>
                    </span>

				</div>
			</li>
			<?php } ?>
		</ul>

		<!-- no data -->
		<?php if (!$print_arr) { ?>
		<p id="board-nodata"><?php echo SET_NODATA_MSG; ?></p>
		<?php } ?>
	</div>

	<!-- paging -->
	<div id="board-paging">
		<?php echo $pagingprint; ?>
	</div>

	<!-- button -->
	<div class="btn-wrap">
		<div class="left">
			<?php echo $ctr_btn; ?>
		</div>
		<div class="right">
			<?php echo $write_btn; ?>
		</div>
	</div>
</form>

<!-- search -->
<form <?php echo $this->sch_form(); ?>>
	<input type="hidden" name="category" value="<?php echo $category; ?>" />

	<select name="where" class="where">
        <option value="all" <?php echo $where_slted['all']; ?>>전체</option>
		<option value="subjectAndArticle" <?php echo $where_slted['subjectAndArticle']; ?>>제목+내용</option>
		<option value="subject" <?php echo $where_slted['subject']; ?>>제목</option>
		<option value="article" <?php echo $where_slted['article']; ?>>내용</option>
		<option value="writer" <?php echo $where_slted['writer']; ?>>작성자</option>
		<option value="mb_id" <?php echo $where_slted['mb_id']; ?>>회원 아이디</option>
	</select>
	<input type="text" name="keyword" class="keyword" value="<?php echo $keyword; ?>" placeholder="검색어를 입력하세요." />
	<button type="submit" class="btn1 small"><i class="fa fa-search"></i> 검색</button>
	<a href="<?php echo $cancel_link; ?>" class="btn2 small">초기화</a>
</form>

<!-- bottom source code -->
<?php if ($is_tf_source_show) { ?>
<div id="mod_board_bottom_source"><?php echo $bottom_source; ?></div>
<?php } ?>
