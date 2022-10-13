Report below:
============
Summary
============
#	Duration	Query
{foreach from=$query_profiling item=row key=counter}
{$row.id}	{$row.duration}	{$row.query}...
{/foreach}

============
Details
============
{foreach from=$query_profiling item=row key=counter}
Details of Query ID {$row.id}({$row.duration})
{if $row.details}
#	Duration	Seq	CPU_USER	CPU_SYST	State
{foreach from=$row.details item=detail}
{$detail.QUERY_ID}	{$detail.DURATION}	{$detail.SEQ}	{$detail.CPU_USER}	{$detail.CPU_SYSTEM}	{$detail.STATE}
{/foreach}
{/if}

{/foreach}
