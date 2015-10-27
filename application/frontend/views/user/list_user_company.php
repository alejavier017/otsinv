<div class="row-fluid">
    <div class="span12">
        <table width="100%" cellpadding="5" cellspacing="5" border="0" class="table table-striped table-bordered table-hover dataTable" id="pagelist_center">
            <thead>
                <tr class="hdr">
                    <th>Company Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
				if(count($rsMapDtl)==0)
                {
                    echo "<tr>";
                    echo '<td colspan="14" style="text-align:center;">No data found.</td>';
                    echo "</tr>";
                }
                else
                {
                    foreach($rsMapDtl as $arrRecord)
                    {
                        $checked = '';
						if($arrRecord['map']=='1')
						{
							$checked = 'checked';
						}
						echo '<tr>';
						echo '<td>'. $arrRecord['com_name'] .'</td>';
						echo '<td class="span1 center"><div class="control-group">
								<div class="controls">
									<input type="checkbox" name="switch-field-1" class="ace-switch ace-switch-6 asn" '.$checked.' userid='.$user_id.' companyid='.$arrRecord['com_id'].'>
									<span class="lbl"></span>
								</div>
							</div></td>';
                        echo '</tr>';
                    }
				}
                ?>
            </tbody>
        </table>
    </div>
</div>
<script type="text/javascript">
	$(".asn").click(function(){
		var userid = $(this).attr('userid');
		var companyid = $(this).attr('companyid');
		$.ajax({
			type:"POST",
			url:"index.php?c=commonajax&m=mapUserCompany",
			data:"userid="+userid+"&companyid="+companyid,
			beforeSend:function(){
			},
			success:function(res){
			}
		});
	});
</script>