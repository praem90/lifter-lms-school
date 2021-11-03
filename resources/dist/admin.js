(()=>{var t,e={912:()=>{jQuery((function(){jQuery("#students-table").dataTable({processing:!0,serverSide:!0,ajax:{url:ajaxurl,data:function(t){t.action="llms_school_get",t.school_id=jQuery("input#llms_school_id").val()}},columns:[{data:"ID",title:"User ID"},{data:"manual_id",title:"User Manual ID",render:function(t){return t||""}},{data:"first_name",title:"First Name"},{data:"last_name",title:"Last Name"},{data:"class",title:"Class"},{data:"section",title:"Section"},{data:"llms_membership",title:"Groups"},{data:"action",title:"Action",render:function(t,e,a){var r=new URL(location.href);r.searchParams.set("page","llms_course_export"),r.searchParams.set("student_id",a.ID),r.searchParams.set("school_id",jQuery("input#llms_school_id").val());var s=r.href;r.searchParams.set("page","llms_quiz_export");var n=r.href;r.searchParams.set("page","llms_assignment_export");var o=r.href;return'<div class="dropdown">\n  \t  \t  \t  \t  \t  <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton'.concat(a.ID,'" data-bs-toggle="dropdown" aria-expanded="false">Reports</button>\n  \t  \t  \t  \t  \t  <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton').concat(a.ID,'">\n    \t\t\t\t\t<li><a class="dropdown-item" href="').concat(s,'">Course</a></li>\n    \t\t\t\t\t<li><a class="dropdown-item" href="').concat(n,'">Quiz</a></li>\n    \t\t\t\t\t<li><a class="dropdown-item" href="').concat(o,'">Assignment</a></li>\n  \t  \t  \t  \t  \t  </ul>\n\t\t\t\t\t</div>')}}]}),jQuery("#groups-table").dataTable({processing:!0,serverSide:!0,ajax:{url:ajaxurl,data:function(t){t.action="llms_group_get",t.school_id=jQuery("input#llms_school_id").val()}},columns:[{data:"ID",title:"Group ID"},{data:"post_title",title:"Group Name",render:function(t,e,a){return'<a href="'.concat(a.guid,'" target="_blank">').concat(t,"</a>")}},{data:"membership",title:"Membership",render:function(t,e,a){return t?'<a href="'.concat(t.guid,'" target="_blank">').concat(t.post_title,"</a>"):"N/A"}},{data:"courses_count",title:"No of Courses"},{data:"class",title:"Class",render:function(t){return t||""}},{data:"section",title:"Section",render:function(t){return t||""}},{data:"students_count",title:"Students Count"},{data:"action",title:"Action",render:function(t,e,a){var r=new URL(location.href);r.searchParams.set("group_id",a.ID),r.searchParams.set("school_id",jQuery("input#llms_school_id").val()),r.searchParams.set("page","llms_course_export");var s=r.href;r.searchParams.set("page","llms_students_export");var n=r.href;r.searchParams.set("page","llms_quiz_export");var o=r.href;r.searchParams.set("page","llms_assignment_export");var l=r.href;return'<div class="dropdown">\n  \t  \t  \t  \t  \t  <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton'.concat(a.ID,'" data-bs-toggle="dropdown" aria-expanded="false">Reports</button>\n  \t  \t  \t  \t  \t  <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton').concat(a.ID,'">\n    \t\t\t\t\t<li><a class="dropdown-item" href="').concat(n,'">Student</a></li>\n    \t\t\t\t\t<li><a class="dropdown-item" href="').concat(s,'">Course</a></li>\n    \t\t\t\t\t<li><a class="dropdown-item" href="').concat(o,'">Quiz</a></li>\n    \t\t\t\t\t<li><a class="dropdown-item" href="').concat(l,'">Assignment</a></li>\n  \t  \t  \t  \t  \t  </ul>\n\t\t\t\t\t</div>')}}]}),jQuery(document).on("click","#export_students",(function(){var t=new URL(jQuery(this).attr("href"));t.searchParams.delete("post"),t.searchParams.append("post",jQuery("input#llms_school_id").val()),t.searchParams.append("school_id",jQuery("input#llms_school_id").val()),t.searchParams.delete("page"),t.searchParams.append("page","llms_students_export"),t.searchParams.append("class",jQuery(this).parents(".row").find("select").eq(0).val()),t.searchParams.append("section",jQuery(this).parents(".row").find("select").eq(1).val()),window.location=t})),jQuery(document).on("click","#export_groups",(function(){var t=new URL(jQuery(this).attr("href"));t.searchParams.delete("post"),t.searchParams.append("post",jQuery("input#llms_school_id").val()),t.searchParams.delete("page"),t.searchParams.append("school_id",jQuery("input#llms_school_id").val()),t.searchParams.append("page","llms_groups_export"),t.searchParams.append("class",jQuery(this).parents(".row").find("select").eq(0).val()),t.searchParams.append("section",jQuery(this).parents(".row").find("select").eq(1).val()),window.location=t})),jQuery(document).on("submit","#group_school_info",(function(t){t.preventDefault(),console.log(this)}))}))},986:()=>{}},a={};function r(t){var s=a[t];if(void 0!==s)return s.exports;var n=a[t]={exports:{}};return e[t](n,n.exports,r),n.exports}r.m=e,t=[],r.O=(e,a,s,n)=>{if(!a){var o=1/0;for(d=0;d<t.length;d++){for(var[a,s,n]=t[d],l=!0,i=0;i<a.length;i++)(!1&n||o>=n)&&Object.keys(r.O).every((t=>r.O[t](a[i])))?a.splice(i--,1):(l=!1,n<o&&(o=n));if(l){t.splice(d--,1);var c=s();void 0!==c&&(e=c)}}return e}n=n||0;for(var d=t.length;d>0&&t[d-1][2]>n;d--)t[d]=t[d-1];t[d]=[a,s,n]},r.o=(t,e)=>Object.prototype.hasOwnProperty.call(t,e),(()=>{var t={808:0,802:0};r.O.j=e=>0===t[e];var e=(e,a)=>{var s,n,[o,l,i]=a,c=0;if(o.some((e=>0!==t[e]))){for(s in l)r.o(l,s)&&(r.m[s]=l[s]);if(i)var d=i(r)}for(e&&e(a);c<o.length;c++)n=o[c],r.o(t,n)&&t[n]&&t[n][0](),t[o[c]]=0;return r.O(d)},a=self.webpackChunklifter_multitenancy=self.webpackChunklifter_multitenancy||[];a.forEach(e.bind(null,0)),a.push=e.bind(null,a.push.bind(a))})(),r.O(void 0,[802],(()=>r(912)));var s=r.O(void 0,[802],(()=>r(986)));s=r.O(s)})();
//# sourceMappingURL=admin.js.map