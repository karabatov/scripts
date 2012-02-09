SET NOCOUNT ON SELECT DISTINCT left(Card.Full_Name,30) as 'Имя пользователя', left(Appointments.Name_appoint,35) as 'Должность', CONVERT(VARCHAR(10),people.out_date,111) as 'Увольнение'
FROM         Card INNER JOIN
                      people ON Card.Auto_Card = people.Auto_Card INNER JOIN
                      PR_CURRENT ON people.pId = PR_CURRENT.pId INNER JOIN
                      Appointments ON PR_CURRENT.Code_Appoint = Appointments.Code_appoint
WHERE people.out_date >= DATEADD(DAY, -7, GETDATE()) AND people.out_date <= GETDATE()